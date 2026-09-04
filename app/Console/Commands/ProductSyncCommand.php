<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProductSyncCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'sync:products {--b|brands} {--c|categories}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Execute the console command.
   */
  public function handle(CsvDecodeAction $action)
  {
    if ($this->option('brands')) {
      Artisan::call('sync:brands');
    }
    if ($this->option('categories')) {
      Artisan::call('sync:categories');
    }

    $server = env('SSG_SERVER');
    $user = env('SSG_USER');
    $pass = env('SSG_PASS');

    $this->info('Rebuilding products');

    $data = "ftp://$user:$pass@$server/Labs/" . env('FEED_PRODUCTS');

    $this->info('Counting CSV rows...');
    $totalRows = $action->countRows($data);

    $this->info('Loading brands, categories, and suppliers...');
    $brands = Brand::pluck('id', 'code');
    $categories = Category::pluck('id', 'code');
    $suppliers = Supplier::pluck('id', 'code');
    $existingProducts = Product::pluck('id', 'code');

    $errors = [];
    $updateData = [];
    $processedCodes = [];
    $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0];
    $chunkSize = 500;
    $maxErrors = 100;

    $progressBar = $this->output->createProgressBar($totalRows);
    $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
    $progressBar->setMessage('Processing products...');
    $progressBar->start();

    foreach ($action->stream($data, ',') as $product) {
      $productCode = $product['item'];
      $progressBar->setMessage("Processing: {$productCode}");

      $failed = false;


      // Check if brand exists
      if (!$brands->has($product['brand'])) {
        if (count($errors) < $maxErrors) {
          $errors[] = [
            'item' => $productCode . ": " . $product['description'],
            'warning' => $product['brand'] . " has not yet been synced. Run `php artisan sync:products -b` to correct this",
          ];
        }
        $failed = true;
      }

      // Check if category exists
      if (!$categories->has($product['webcat'])) {
        if (count($errors) < $maxErrors) {
          $errors[] = [
            'item' => $productCode . ": " . $product['description'],
            'warning' => $product['webcat'] . " has not yet been synced. Run `php artisan sync:products -c` to correct this",
          ];
        }
        $failed = true;
      }

      if (!$product['imad']) {
        $failed = true;
      }

      if (!$failed) {
        $isUpdate = $existingProducts->has($productCode);

        if ($isUpdate) {
          $stats['updated']++;
        } else {
          $stats['created']++;
        }

        $processedCodes[] = $productCode;

        // Look up supplier IDs from the preloaded collection (much faster than database queries)
        $manufacturerId = !empty($product['manufacturer']) ? $suppliers->get($product['manufacturer']) : null;
        $importerId = !empty($product['importer']) ? $suppliers->get($product['importer']) : null;
        $officeId = !empty($product['office']) ? $suppliers->get($product['office']) : null;

        // If no office supplier is specified, fall back to importer
        if ($officeId === null && $importerId !== null) {
          $officeId = $importerId;
        }



        $updateData[] = [
          'code' => $productCode,
          'variant' => $product['variant'] ?: null,
          'name' => $product['description'],
          'brand_id' => $brands->get($product['brand']),
          'category_id' => $categories->get($product['webcat']),
          'ssp_ch' => $product['ssp_ch'] ?: 0,
          'trade_ch' => $product['trade_ch'] ?: 0,
          'qty_break_ch' => $product['qty_ch'],
          'qty_discount_ch' => $product['break_ch'] ?: 0,
          'promo_ch' => $product['fixed_ch'] ?: 0,
          'promo_start' => $product['fixed_start'] ?: null,
          'promo_end' => $product['fixed_end'] ?: null,
          'ean' => $product['ean'],
          'bundle' => $product['bundle'],
          'esd' => $product['esd'] == "-1" ? 1 : 0,
          'freight' => $product['freight'] == "-1" ? 1 : 0,
          'published' => $product['published_ch'] == "-1" ? 1 : 0,
          'embargo' => $product['embargo'] == "-1" ? 1 : 0,
          'manufacturer_supplier_id' => $manufacturerId,
          'office_supplier_id' => $officeId,
          'importer_supplier_id' => $importerId,
          'mpn' => $product['mpn'],
          'updated_at' => now(),
          'created_at' => now(),
        ];

        // Flush to database in chunks to prevent memory buildup
        if (count($updateData) >= $chunkSize) {
          Product::upsert(
            $updateData,
            ['code'],
            [
              'name',
              'brand_id',
              'category_id',
              'ssp_ch',
              'trade_ch',
              'qty_break_ch',
              'qty_discount_ch',
              'freight',
              'promo_ch',
              'promo_start',
              'promo_end',
              'ean',
              'bundle',
              'variant',
              'esd',
              'published',
              'updated_at',
              'manufacturer_supplier_id',
              'office_supplier_id',
              'importer_supplier_id',
              'mpn',
              'embargo'
            ]
          );
          $updateData = [];
        }
      } else {
        $stats['skipped']++;
      }

      $progressBar->advance();
    }

    // Flush any remaining data
    if (!empty($updateData)) {
      Product::upsert(
        $updateData,
        ['code'],
        [
          'name',
          'brand_id',
          'category_id',
          'ssp_ch',
          'trade_ch',
          'qty_break_ch',
          'qty_discount_ch',
          'freight',
          'promo_ch',
          'promo_start',
          'promo_end',
          'ean',
          'bundle',
          'esd',
          'variant',
          'published',
          'updated_at',
          'manufacturer_supplier_id',
          'office_supplier_id',
          'importer_supplier_id',
          'mpn',
          'embargo'
        ]
      );
      $updateData = [];
    }

    $progressBar->setMessage('Complete!');
    $progressBar->finish();
    $this->newLine(2);

    $this->info("✓ Products processed successfully!");
    $this->table(
      ['Action', 'Count'],
      [
        ['Created', $stats['created']],
        ['Updated', $stats['updated']],
        ['Skipped', $stats['skipped']],
      ]
    );

    // Housekeeping - find discontinued products using database query instead of loading all into memory
    $toDelete = Product::where('published', 1)
      ->whereNotIn('code', $processedCodes)
      ->get(['id', 'code', 'name']);

    unset($processedCodes);

    foreach ($toDelete as $del) {
      $this->components->twoColumnDetail($del->name, $del->code);
    }

    /*        if($toDelete->count() > 0 && $this->confirm('Mark '.$toDelete->count().' as discontinued?'))
        {*/
    $this->info("Updating missing/discontinued items...");
    Product::whereIn('id', $toDelete->pluck('id'))->update(['published' => 0]);
    $this->info("Done!");
    /*        } else {
            $this->info('Nothing to update');
        } */

    if (!empty($errors)) {
      $this->newLine();
      $this->error('⚠ Errors encountered:');
      $this->newLine();

      foreach ($errors as $error) {
        $this->line($error['item']);
        $this->warn('  → ' . $error['warning']);
        $this->newLine();
      }
    }
  }
}
