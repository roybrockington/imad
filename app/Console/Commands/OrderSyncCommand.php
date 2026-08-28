<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Account;
use App\Models\Address;
use App\Models\Country;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Region;
use App\Models\User;
use Illuminate\Console\Command;

class OrderSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:orders {--recalculate : Recalculate totals for existing orders}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync orders from CSV feed';

    /**
     * Recalculate order totals, shipping, insurance, and freight based on items and account settings
     */
    protected function recalculateOrderValues(Order $order, $items, $currency)
    {
        // Calculate total from items
        $total = $items->sum(function ($item) {
            $quantity = (int)($item['pending'] ?? 0) + (int)($item['shipped'] ?? 0);
            $unitPrice = (float)($item['unitprice'] ?? 0);
            return $unitPrice * $quantity;
        });

        // Get account for fee calculations
        $account = $order->account;

        // Calculate insurance if account has insurance percentage
        $insurance = 0;
        if ($account && $account->insurance > 0) {
            $insurance = ($total * $account->insurance) / 100;
        }

        // Calculate shipping and freight charges
        $shipping = 0;
        $freight = 0;

        // Get address for shipping/freight lookup
        $address = $order->address ?? Address::where('account_id', $order->account_id)->first();

        // Calculate shipping (only if account doesn't have free shipping)
        if ($address && (!$account || !$account->freeShipping)) {
            $country = Country::where('code', $address->country)
                ->orWhere('name', $address->country)
                ->first();

            if ($country) {
                $currencyLower = strtolower($currency);
                $shippingColumn = 'shipping_' . $currencyLower;
                if (property_exists($country, $shippingColumn) || isset($country->$shippingColumn)) {
                    $shipping = $country->$shippingColumn ?? 0;
                }
            }
        }

        // Calculate freight (check if any product has freight = true)
        $hasFreightProduct = false;
        foreach ($items as $item) {
            $productCode = $item['item'] ?? null;
            $product = Product::where('code', $productCode)->first();
            if ($product && $product->freight) {
                $hasFreightProduct = true;
                break;
            }
        }

        if ($hasFreightProduct && $address) {
            $country = Country::where('code', $address->country)
                ->orWhere('name', $address->country)
                ->first();

            if ($country) {
                $currencyLower = strtolower($currency);
                $freightColumn = 'freight_' . $currencyLower;
                if (property_exists($country, $freightColumn) || isset($country->$freightColumn)) {
                    $freight = $country->$freightColumn ?? 0;
                }
            }
        }

        return [
            'total' => $total,
            'insurance' => $insurance,
            'shipping' => $shipping,
            'freight' => $freight,
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Syncing orders');

        $data = "ftp://$user:$pass@$server/Labs/".env('FEED_ORDERS');

        $this->info('Loading CSV data...');
        $orderItems = $action->handle($data, ',');

        // Update existing order references to prevent duplicates
        $orphans = Order::where('reference', null)->get();
        $updatedCount = 0;

        $orderIdToReference = collect($orderItems)
            ->groupBy('id')
            ->map(fn($items) => $items->first()['order'])
            ->toArray();

        $this->info('Updating existing order references...');
        $orderIdToReference = collect($orderItems)
            ->groupBy('id')
            ->map(fn($items) => $items->first()['order'])
            ->toArray();

        foreach ($orphans as $orphan) {
            $updated = $orphan->update([
                'reference' => $orderIdToReference[$orphan->id],
            ]);
            $updatedCount += $updated;
        }

        if ($updatedCount > 0) {
            $this->info("✓ Updated {$updatedCount} order references");
        }

        // Load products and accounts for lookup
        $this->info('Loading products and accounts...');
        $products = Product::pluck('id', 'code');


        // Group order items by unique order reference
        $uniqueOrders = collect($orderItems)->groupBy('order');


        $this->info('Processing ' . $uniqueOrders->count() . ' unique orders...');

        $stats = ['created' => 0, 'updated' => 0, 'items_created' => 0];
        $progressBar = $this->output->createProgressBar($uniqueOrders->count());
        $progressBar->start();


        foreach ($uniqueOrders as $orderReference => $items) {
            // Get the first item to extract order-level data
            $firstItem = $items->first();

            // Calculate total (unitprice * quantity for each item)
            $total = $items->sum(function ($item) {
                $quantity = (int)($item['pending'] ?? 0) + (int)($item['shipped'] ?? 0);
                $unitPrice = (float)($item['unitprice'] ?? 0);

                return $unitPrice * $quantity;
            });

            // Look up account by code and region
            $accountCode = $firstItem['account'] ?? null;
            $regionId = Region::firstWhere('code', $firstItem['region'])?->id ?? null;
            $accountId = Account::firstWhere([
                'code' => $accountCode,
                'region_id' => $regionId
            ])->id;

            $currency = $firstItem['currency'] ?? 'EUR';

            // Get account for fee calculations
            $account = Account::firstWhere([
                'code' => $accountCode,
                'region_id' => $regionId
            ]);

            // Calculate insurance if account has insurance percentage
            $insurance = 0;
            if ($account && $account->insurance > 0) {
                $insurance = ($total * $account->insurance) / 100;
            }

            // Calculate shipping and freight charges
            $shipping = 0;
            $freight = 0;

            // Get address for shipping/freight lookup
            $address = null;
            if (!empty($firstItem['postcode']) && !empty($firstItem['address'])) {
                $address = Address::where('account_id', $accountId)
                    ->where('address2', $firstItem['address'])
                    ->where('postcode', $firstItem['postcode'])
                    ->first();
            }

            // Fallback: if no address found from CSV data, use account's first address
            if (!$address && $accountId) {
                $address = Address::where('account_id', $accountId)->first();
            }

            // Calculate shipping (only if account doesn't have free shipping)
            if ($address && (!$account || !$account->freeShipping)) {
                $country = Country::where('code', $address->country)
                    ->orWhere('name', $address->country)
                    ->first();

                if ($country) {
                    $currencyLower = strtolower($currency);
                    $shippingColumn = 'shipping_' . $currencyLower;
                    if (property_exists($country, $shippingColumn) || isset($country->$shippingColumn)) {
                        $shipping = $country->$shippingColumn ?? 0;
                    }
                }
            }

            // Calculate freight (check if any product has freight = true)
            $hasFreightProduct = false;
            foreach ($items as $item) {
                $productCode = $item['item'] ?? null;
                $product = Product::where('code', $productCode)->first();
                if ($product && $product->freight) {
                    $hasFreightProduct = true;
                    break;
                }
            }

            if ($hasFreightProduct && $address) {
                $country = Country::where('code', $address->country)
                    ->orWhere('name', $address->country)
                    ->first();

                if ($country) {
                    $currencyLower = strtolower($currency);
                    $freightColumn = 'freight_' . $currencyLower;
                    if (property_exists($country, $freightColumn) || isset($country->$freightColumn)) {
                        $freight = $country->$freightColumn ?? 0;
                    }
                }
            }

            // Parse created_at date from CSV date field (DD.MM.YYYY HH:MM:SS)
            $createdAt = null;
            if (!empty($firstItem['date'])) {
                try {
                    // Trim any whitespace and parse the date
                    $dateString = trim($firstItem['date']);
                    $createdAt = \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', $dateString);
                } catch (\Exception $e) {
                    // If parsing fails, try without time component
                    try {
                        $dateString = trim($firstItem['date']);
                        // Extract just the date part (DD.MM.YYYY)
                        if (preg_match('/^(\d{2}\.\d{2}\.\d{4})/', $dateString, $matches)) {
                            $createdAt = \Carbon\Carbon::createFromFormat('d.m.Y', $matches[1])->startOfDay();
                        } else {
                            $createdAt = now();
                        }
                    } catch (\Exception $e2) {
                        $createdAt = now();
                    }
                }
            } else {
                $createdAt = now();
            }


            // Find or create the order
            // First, try to find existing order by CSV ID if provided
            $csvOrderId = !empty($firstItem['id']) ? $firstItem['id'] : null;
            $order = null;

            if ($csvOrderId) {
                $order = Order::find($csvOrderId);
            }

            // If not found by ID, try to find by reference
            if (!$order) {
                $order = Order::where('reference', $orderReference)->first();
            }


            // Create or update the order
            if ($order) {
                // Update existing order - recalculate values from current CSV items
                $recalculated = $this->recalculateOrderValues($order, $items, $currency);

                $updateData = [
                    'reference' => $orderReference,
                    'account_id' => $accountId,
                    'total' => $recalculated['total'],
                    'insurance' => $recalculated['insurance'],
                    'shipping' => $recalculated['shipping'],
                    'freight' => $recalculated['freight'],
                    'currency' => $currency,
                    'status' => 'pending',
                    'notes' => null,
                    'po' => $firstItem['po'],
                    'postcode' => $firstItem['postcode'],
                    'address' => $firstItem['address'],
                    'address_id' => $address?->id ?? null
                ];

                // Only update user_id if it's currently null
                if (is_null($order->user_id)) {
                    $updateData['user_id'] = User::first()->id ?? 1;
                }

                $order->update($updateData);
            } else {
                // Create new order with specific ID if provided from CSV
                $orderData = [
                    'reference' => $orderReference,
                    'user_id' => User::first()->id ?? 1,
                    'account_id' => $accountId,
                    'total' => $total,
                    'insurance' => $insurance,
                    'shipping' => $shipping,
                    'freight' => $freight,
                    'currency' => $currency,
                    'status' => 'pending',
                    'notes' => null,
                    'po' => $firstItem['po'],
                    'postcode' => $firstItem['postcode'],
                    'address' => $firstItem['address'],
                    'address_id' => $address?->id ?? null
                ];

                if ($csvOrderId) {
                    $orderData['id'] = $csvOrderId;
                }

                $order = Order::create($orderData);
            }

            // Manually update created_at without triggering updated_at change
            $order->timestamps = false;
            $order->created_at = $createdAt;
            $order->save();
            $order->timestamps = true;

            $imported = $order->wasRecentlyCreated;


            if ($imported) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            // Create order items for this order
            foreach ($items as $item) {
                $productCode = $item['item'] ?? null;
                $productId = $products->get($productCode);

                if (!$productId) {
                    continue; // Skip if product not found
                }

                // Get product details for order item
                $product = Product::find($productId);

                // Parse shipdate from DD.MM.YYYY HH:MM:SS to Y-m-d format
                $shippedDate = null;
                if (!empty($item['shipdate'])) {
                    try {
                        $shippedDate = \Carbon\Carbon::createFromFormat('d.m.Y H:i:s', $item['shipdate'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        // If parsing fails, leave as null
                    }
                }

                OrderItem::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'product_id' => $productId,
                    ],
                    [
                        'quantity' => (int)($item['pending'] ?? 0) + (int)($item['shipped'] ?? 0),
                        'price' => (float)($item['unitprice'] ?? 0),
                        'currency' => $currency,
                        'product_code' => $productCode,
                        'product_name' => $product->name ?? '',
                        'shipped' => $item['shipped'] ?? 0,
                        'courier' => !empty($item['courier']) ? $item['courier'] : null,
                        'tracking' => !empty($item['tracking']) ? $item['tracking'] : null,
                        'shipped_date' => $shippedDate,
                        'imported' => $imported,
                    ]
                );

                $stats['items_created']++;
            }

            // After creating/updating all order items, recalculate order total from actual saved items
            $order->refresh(); // Reload the order with fresh items
            $actualTotal = $order->items->sum(function ($orderItem) {
                return $orderItem->price * $orderItem->quantity;
            });

            // Recalculate insurance based on actual total
            $actualInsurance = 0;
            if ($account && $account->insurance > 0) {
                $actualInsurance = ($actualTotal * $account->insurance) / 100;
            }

            // Recalculate shipping based on actual order data
            $actualShipping = 0;
            if ($address && (!$account || !$account->freeShipping)) {
                $country = Country::where('code', $address->country)
                    ->orWhere('name', $address->country)
                    ->first();

                if ($country) {
                    $currencyLower = strtolower($currency);
                    $shippingColumn = 'shipping_' . $currencyLower;
                    if (property_exists($country, $shippingColumn) || isset($country->$shippingColumn)) {
                        $actualShipping = $country->$shippingColumn ?? 0;
                    }
                }
            }

            // Recalculate freight based on actual saved items
            $actualFreight = 0;
            $hasFreightProduct = $order->items->contains(function ($orderItem) {
                return $orderItem->product && $orderItem->product->freight;
            });

            if ($hasFreightProduct && $address) {
                $country = Country::where('code', $address->country)
                    ->orWhere('name', $address->country)
                    ->first();

                if ($country) {
                    $currencyLower = strtolower($currency);
                    $freightColumn = 'freight_' . $currencyLower;
                    if (property_exists($country, $freightColumn) || isset($country->$freightColumn)) {
                        $actualFreight = $country->$freightColumn ?? 0;
                    }
                }
            }

            // Update order with recalculated values if they differ
            $updates = [];
            if ($actualTotal != $order->total) {
                $updates['total'] = $actualTotal;
            }
            if ($actualInsurance != $order->insurance) {
                $updates['insurance'] = $actualInsurance;
            }
            if ($actualShipping != $order->shipping) {
                $updates['shipping'] = $actualShipping;
            }
            if ($actualFreight != $order->freight) {
                $updates['freight'] = $actualFreight;
            }

            if (!empty($updates)) {
                $order->update($updates);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('✓ Orders processed successfully!');
        $this->table(
            ['Action', 'Count'],
            [
                ['Orders Created', $stats['created']],
                ['Orders Updated', $stats['updated']],
                ['Order Items Created', $stats['items_created']],
            ]
        );

        $this->info('All done');
    }
}
