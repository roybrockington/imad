<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Product;
use Illuminate\Console\Command;

class StockSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update available stock levels from Sage';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Updating EU stock levels');

        $src = "ftp://$user:$pass@$server/Labs/ssg_products.csv";
        $stocks = $action->handle($src, ',');

        $this->info('Processing ' . count($stocks) . ' stock items...');

        $updatedCount = 0;

        // Process in chunks to avoid memory issues
        $chunkSize = 500;
        $stocksCollection = collect($stocks);

        foreach ($stocksCollection->chunk($chunkSize) as $stockChunk) {
            $itemCodes = $stockChunk->pluck('item')->toArray();

            // Fetch only the products in this chunk
            $products = Product::whereIn('code', $itemCodes)
                ->get(['id', 'code', 'stock', 'eta'])
                ->keyBy('code');

            $updates = [];

            foreach ($stockChunk as $stock) {
                if (isset($products[$stock['item']])) {
                    $product = $products[$stock['item']];

                    // Only update if values have changed
                    if ($product->stock != $stock['availablestock'] || $product->eta != $stock['eta']) {
                        $updates[] = [
                            'id' => $product->id,
                            'stock' => $stock['availablestock'],
                            'eta' => $stock['eta'],
                        ];
                    }
                }
            }

            // Perform updates for this chunk
            if (!empty($updates)) {
                // Process updates in smaller batches
                foreach (array_chunk($updates, 100) as $batch) {
                    \DB::transaction(function () use ($batch, &$updatedCount) {
                        foreach ($batch as $update) {
                            \DB::table('products')
                                ->where('id', $update['id'])
                                ->update([
                                    'stock' => $update['stock'],
                                    'eta' => $update['eta'],
                                    'updated_at' => now(),
                                ]);
                        }
                        $updatedCount += count($batch);
                    });
                }
            }

            // Free memory
            unset($products, $updates);
        }

        $this->info("Updated {$updatedCount} products");

        //UK
        $this->info('Updating UK stock levels');

        $src = "ftp://$user:$pass@$server/SCV/products.csv";
        $ukstocks = $action->handle($src, ',');

        $this->info('Processing ' . count($ukstocks) . ' UK stock items...');

        $ukUpdatedCount = 0;

        // Process in chunks to avoid memory issues
        $ukStocksCollection = collect($ukstocks);

        foreach ($ukStocksCollection->chunk($chunkSize) as $ukStockChunk) {
            $ukItemCodes = $ukStockChunk->pluck('item')->toArray();

            // Fetch only the products in this chunk
            $ukProducts = Product::whereIn('code', $ukItemCodes)
                ->get(['id', 'code', 'stock_uk', 'eta_uk'])
                ->keyBy('code');

            $ukUpdates = [];

            foreach ($ukStockChunk as $ukstock) {
                if (isset($ukProducts[$ukstock['item']])) {
                    $product = $ukProducts[$ukstock['item']];

                    // Only update if values have changed
                    if ($product->stock_uk != $ukstock['availablestock'] || $product->eta_uk != $ukstock['eta']) {
                        $ukUpdates[] = [
                            'id' => $product->id,
                            'stock_uk' => $ukstock['availablestock'],
                            'eta_uk' => $ukstock['eta'],
                        ];
                    }
                }
            }

            // Perform updates for this chunk
            if (!empty($ukUpdates)) {
                // Process updates in smaller batches
                foreach (array_chunk($ukUpdates, 100) as $batch) {
                    \DB::transaction(function () use ($batch, &$ukUpdatedCount) {
                        foreach ($batch as $update) {
                            \DB::table('products')
                                ->where('id', $update['id'])
                                ->update([
                                    'stock_uk' => $update['stock_uk'],
                                    'eta_uk' => $update['eta_uk'],
                                    'updated_at' => now(),
                                ]);
                        }
                        $ukUpdatedCount += count($batch);
                    });
                }
            }

            // Free memory
            unset($ukProducts, $ukUpdates);
        }

        $this->info("Updated {$ukUpdatedCount} UK products");

        $this->info('Done!');
    }
}
