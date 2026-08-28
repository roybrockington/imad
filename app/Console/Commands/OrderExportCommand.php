<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class OrderExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export orders to Sage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $ordersToExport = Order::where('reference', '')->where('status', '!=', 'cancelled')->pluck('id');

        $itemsToExport = OrderItem::with(['order.account.region', 'product'])
            ->whereIn('order_id', $ordersToExport)
            ->where([
                'imported' => 0,
                'exported' => 0,
            ])->get();

        // Manually load addresses - the relationship seems broken
        $addressIds = $itemsToExport->pluck('order.address_id')->unique()->filter();
        $addresses = \App\Models\Address::whereIn('id', $addressIds)->get()->keyBy('id');

        // No items error
        if ($itemsToExport->isEmpty()) {
            $this->info('No orders to export.');
            return Command::SUCCESS;
        }

        // Separate items by region
        $exportByRegion = [
            'eu' => [],
            'uk' => []
        ];

        foreach ($itemsToExport as $item) {
            // Skip items without required relationships
            $missingRelationships = [];
            if (!$item->order) {
                $missingRelationships[] = 'order';
            } else {
                if (!$item->order->account) {
                    $missingRelationships[] = 'order.account';
                }
                // Check manually loaded addresses
                $address = $addresses->get($item->order->address_id);
                if (!$address) {
                    $missingRelationships[] = 'order.address';
                }
            }
            if (!$item->product) {
                $missingRelationships[] = 'product';
            }

            if (!empty($missingRelationships)) {
                $this->warn("Skipping order item {$item->id}: missing " . implode(', ', $missingRelationships));
                continue;
            }

            $regionCode = $item->order->account->region->code ?? 'eu'; // Default to EU if no region
            $address = $addresses->get($item->order->address_id);

            $rowData = [
                'AccountNo' => $item->order->account->code,
                'AddressNo' => $address->code,
                'ItemNumber' => $item->product->code,
                'Qty' => $item->quantity,
                'UnitPrice' => $item->price,
                'CurrencyCode' => $item->currency,
                'HasNetPrice' => true, // true = ex vat pricing (b2b)
                'ShopOrderID' => $item->order_id,
                'Reference' => $item->order->po
            ];

            if (isset($exportByRegion[$regionCode])) {
                $exportByRegion[$regionCode][] = $rowData;
            } else {
                // If region is not EU or UK, default to EU
                $exportByRegion['eu'][] = $rowData;
            }
        }

        // Process each region's export
        $exportedItems = collect();

        foreach ($exportByRegion as $region => $export) {
            if (empty($export)) {
                $this->info("No orders for {$region} region.");
                continue;
            }

            $filename = "order_export_b2b_{$region}.csv";
            $tempPath = storage_path('app/' . $filename);

            // Upload to FTP
            try {
                $ftpServer = config('services.ssg.server');
                $ftpUsername = config('services.ssg.user');
                $ftpPassword = config('services.ssg.pass');

                $ftpConnection = ftp_connect($ftpServer);

                if (!$ftpConnection) {
                    $this->error("Could not connect to FTP server: {$ftpServer}");
                    continue;
                }

                $login = ftp_login($ftpConnection, $ftpUsername, $ftpPassword);

                if (!$login) {
                    $this->error('FTP login failed. Please check credentials');
                    ftp_close($ftpConnection);
                    continue;
                }

                // Passive mode
                ftp_pasv($ftpConnection, true);

                $remoteDir = 'Unimog';
                if (!@ftp_chdir($ftpConnection, $remoteDir)) {
                    $this->error("Could not change to directory: {$remoteDir}");
                    ftp_close($ftpConnection);
                    continue;
                }

                // Check if file exists on FTP server
                $remoteFiles = ftp_nlist($ftpConnection, '.');
                $fileExists = $remoteFiles && in_array($filename, $remoteFiles);

                if ($fileExists) {
                    // Download existing file
                    $this->info("[{$region}] File exists on FTP server. Downloading for appending...");

                    if (!ftp_get($ftpConnection, $tempPath, $filename, FTP_BINARY)) {
                        $this->error("[{$region}] Failed to download existing file from FTP server");
                        ftp_close($ftpConnection);
                        continue;
                    }

                    // Append new rows without headers
                    $file = fopen($tempPath, 'a');

                    foreach ($export as $row) {
                        fputcsv($file, $row);
                    }

                    fclose($file);

                    $this->info("[{$region}] Appended " . count($export) . " new rows to existing CSV");
                } else {
                    // Create new file with headers
                    $this->info("[{$region}] File does not exist on FTP server. Creating new file...");

                    $file = fopen($tempPath, 'w');

                    fputcsv($file, array_keys($export[0]));

                    foreach ($export as $row) {
                        fputcsv($file, $row);
                    }

                    fclose($file);

                    $this->info("[{$region}] CSV file created: {$filename}");
                }

                $upload = ftp_put($ftpConnection, $filename, $tempPath, FTP_BINARY);

                if ($upload) {
                    $this->info("[{$region}] File uploaded successfully to FTP server");

                    // Track items from this region that were successfully exported
                    $regionItems = $itemsToExport->filter(function ($item) use ($region) {
                        $itemRegion = $item->order->account->region->code ?? 'eu';
                        return $itemRegion === $region || ($itemRegion !== 'eu' && $itemRegion !== 'uk' && $region === 'eu');
                    });

                    foreach ($regionItems as $item) {
                        $exportedItems->push($item);
                    }

                    $this->info("[{$region}] " . $regionItems->count() . ' order items marked for export');
                } else {
                    $this->error("[{$region}] Failed to upload file to FTP server");
                    ftp_close($ftpConnection);
                    continue;
                }

                ftp_close($ftpConnection);
                unlink($tempPath);

            } catch (\Exception $e) {
                $this->error("[{$region}] FTP upload error: " . $e->getMessage());

                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            }
        }

        // Mark all successfully exported items
        if ($exportedItems->count() > 0) {
            foreach ($exportedItems as $item) {
                $item->update(['exported' => 1]);
            }
            $this->info('Total ' . $exportedItems->count() . ' order items marked as exported');
        }

        return Command::SUCCESS;
    }
}
