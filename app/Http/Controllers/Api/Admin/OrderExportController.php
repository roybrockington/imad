<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderExportController extends Controller
{
    /**
     * Get all unexported order items with their order and product details.
     * Only includes items where the order reference is null or empty.
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderItem::with([
            'order.user',
            'order.account.region',
            'order.address',
            'product'
        ])
        ->where('exported', false)
        ->whereHas('order', function ($q) {
            $q->where(function ($q) {
                $q->whereNull('reference')
                  ->orWhere('reference', '');
            });
        })
        ->orderBy('created_at', 'desc');

        // Filter by account region if provided
        if ($request->has('region')) {
            $query->whereHas('order.account.region', function ($q) use ($request) {
                $q->where('code', $request->input('region'));
            });
        }

        // Search by product code or name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('product_code', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 50);
        $orderItems = $query->paginate($perPage);

        return response()->json($orderItems);
    }

    /**
     * Mark order items as exported.
     */
    public function markAsExported(Request $request): JsonResponse
    {
        $request->validate([
            'order_item_ids' => 'required|array',
            'order_item_ids.*' => 'exists:order_items,id'
        ]);

        $count = OrderItem::whereIn('id', $request->input('order_item_ids'))
            ->update(['exported' => true]);

        return response()->json([
            'message' => "Successfully marked {$count} order item(s) as exported.",
            'count' => $count
        ]);
    }

    /**
     * Get export queue statistics.
     * Only counts items where the order reference is null or empty.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_unexported' => OrderItem::where('exported', false)
                ->whereHas('order', function ($q) {
                    $q->where(function ($q) {
                        $q->whereNull('reference')
                          ->orWhere('reference', '');
                    });
                })
                ->count(),
            'total_exported' => OrderItem::where('exported', true)->count(),
            'by_region' => OrderItem::where('exported', false)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('accounts', 'orders.account_id', '=', 'accounts.id')
                ->join('regions', 'accounts.region_id', '=', 'regions.id')
                ->where(function ($q) {
                    $q->whereNull('orders.reference')
                      ->orWhere('orders.reference', '');
                })
                ->selectRaw('regions.code, regions.name, count(*) as count')
                ->groupBy('regions.code', 'regions.name')
                ->get()
                ->map(function ($item) {
                    return [
                        'code' => $item->code,
                        'name' => $item->name,
                        'count' => $item->count
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Export selected order items to CSV and upload to FTP (Sage export).
     * Similar to the OrderExportCommand but only for selected items.
     */
    public function exportToSage(Request $request): JsonResponse
    {
        $request->validate([
            'order_item_ids' => 'required|array',
            'order_item_ids.*' => 'exists:order_items,id'
        ]);

        $itemsToExport = OrderItem::with(['order.account.region', 'product'])
            ->whereIn('id', $request->input('order_item_ids'))
            ->where([
                'imported' => 0,
                'exported' => 0,
            ])->get();

        // Manually load addresses - the relationship seems broken
        $addressIds = $itemsToExport->pluck('order.address_id')->unique()->filter();
        $addresses = \App\Models\Address::whereIn('id', $addressIds)->get()->keyBy('id');

        // No items error
        if ($itemsToExport->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No valid items to export (items may have already been exported).'
            ], 400);
        }

        // Separate items by region
        $exportByRegion = [
            'eu' => [],
            'uk' => []
        ];

        $skippedItems = [];

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
                $skippedItems[] = [
                    'id' => $item->id,
                    'reason' => 'missing ' . implode(', ', $missingRelationships)
                ];
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
        $errors = [];

        foreach ($exportByRegion as $region => $export) {
            if (empty($export)) {
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
                    $errors[] = "[{$region}] Could not connect to FTP server: {$ftpServer}";
                    continue;
                }

                $login = ftp_login($ftpConnection, $ftpUsername, $ftpPassword);

                if (!$login) {
                    $errors[] = "[{$region}] FTP login failed. Please check credentials";
                    ftp_close($ftpConnection);
                    continue;
                }

                // Passive mode
                ftp_pasv($ftpConnection, true);

                $remoteDir = 'Unimog';
                if (!@ftp_chdir($ftpConnection, $remoteDir)) {
                    $errors[] = "[{$region}] Could not change to directory: {$remoteDir}";
                    ftp_close($ftpConnection);
                    continue;
                }

                // Check if file exists on FTP server
                $remoteFiles = ftp_nlist($ftpConnection, '.');
                $fileExists = $remoteFiles && in_array($filename, $remoteFiles);

                if ($fileExists) {
                    // Download existing file and append
                    if (!ftp_get($ftpConnection, $tempPath, $filename, FTP_BINARY)) {
                        $errors[] = "[{$region}] Failed to download existing file from FTP server";
                        ftp_close($ftpConnection);
                        continue;
                    }

                    // Append new rows without headers
                    $file = fopen($tempPath, 'a');
                    foreach ($export as $row) {
                        fputcsv($file, $row);
                    }
                    fclose($file);
                } else {
                    // Create new file with headers
                    $file = fopen($tempPath, 'w');
                    fputcsv($file, array_keys($export[0]));
                    foreach ($export as $row) {
                        fputcsv($file, $row);
                    }
                    fclose($file);
                }

                $upload = ftp_put($ftpConnection, $filename, $tempPath, FTP_BINARY);

                if ($upload) {
                    // Track items from this region that were successfully exported
                    $regionItems = $itemsToExport->filter(function ($item) use ($region) {
                        $itemRegion = $item->order->account->region->code ?? 'eu';
                        return $itemRegion === $region || ($itemRegion !== 'eu' && $itemRegion !== 'uk' && $region === 'eu');
                    });

                    foreach ($regionItems as $item) {
                        $exportedItems->push($item);
                    }
                } else {
                    $errors[] = "[{$region}] Failed to upload file to FTP server";
                    ftp_close($ftpConnection);
                    continue;
                }

                ftp_close($ftpConnection);
                unlink($tempPath);

            } catch (\Exception $e) {
                $errors[] = "[{$region}] FTP upload error: " . $e->getMessage();
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
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully exported {$exportedItems->count()} order item(s) to Sage.",
            'exported_count' => $exportedItems->count(),
            'skipped_count' => count($skippedItems),
            'skipped_items' => $skippedItems,
            'errors' => $errors
        ]);
    }
}
