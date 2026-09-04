<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Product;
use App\Models\ProductDescription;
use Illuminate\Console\Command;

class ProductDescriptionSyncCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'sync:descriptions';

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
    // The index of ~15k items × 5 langs × 16 fields exceeds the default
    // 128 MB CLI limit. This only affects this process — not web requests.
    ini_set('memory_limit', '512M');

    $server = env('SSG_SERVER');
    $user = env('SSG_USER');
    $pass = env('SSG_PASS');

    $this->info('Rebuilding descriptions');

    $csvPath = "ftp://$user:$pass@$server/iMAD/" . env('FEED_PRODUCT_DESCRIPTIONS');

    $products = Product::pluck('id', 'code');

    // Stream the CSV row-by-row and build a compact index keyed by [item][lang].
    // This avoids holding the full dataset as a Laravel Collection, which was
    // consuming the majority of the 128 MB limit.
    $this->info('Streaming and indexing CSV data...');

    // Build a set of known product codes for fast lookup during CSV streaming.
    // This lets us discard CSV rows that don't match any product, keeping
    // $index as small as possible.
    $productCodes = $products->keys()->flip()->all(); // [code => true]

    $fields = ['name1', 'name2', 'text1', 'text2', 'img1', 'img2', 'img3', 'img4', 'img5', 'img6', 'alt1', 'alt2', 'alt3', 'alt4', 'alt5', 'alt6'];
    $index = [];
    $totalRows = 0;

    foreach ($action->stream($csvPath, ',') as $row) {
      $item = $row['item'] ?? null;
      $lang = $row['lang'] ?? null;
      if ($item === null || $lang === null) {
        continue;
      }
      // Skip rows with no matching product — keeps $index lean
      if (!isset($productCodes[$item])) {
        continue;
      }
      // Store only the fields we actually use
      $compact = [];
      foreach ($fields as $f) {
        $compact[$f] = $row[$f] ?? null;
      }
      $index[$item][$lang] = $compact;
      $totalRows++;
    }

    unset($productCodes); // no longer needed

    $this->info('Loaded ' . $totalRows . ' rows, grouped into ' . count($index) . ' unique items');

    $skippedNoMatches = 0;
    $skippedMissingLangs = 0;
    $syncedCount = 0;
    $sampleLogged = false;

    $upsertFields = [
      'name1_de',
      'name2_de',
      'text1_de',
      'text2_de',
      'name1_fr',
      'name2_fr',
      'text1_fr',
      'text2_fr',
      'name1_it',
      'name2_it',
      'text1_it',
      'text2_it',
      'image1',
      'image2',
      'image3',
      'image4',
      'image5',
      'image6',
      'alt1',
      'alt2',
      'alt3',
      'alt4',
      'alt5',
      'alt6',
      'updated_at',
      'created_at',
    ];

    $this->info('Saving to database...');
    $progressBar = $this->output->createProgressBar($products->count());
    $progressBar->start();

    // Process products in chunks of 100 to keep each upsert (100 × 34 fields)
    // well within memory limits.
    foreach ($products->chunk(100) as $chunk) {
      $updateData = [];

      foreach ($chunk as $code => $productId) {
        $byLang = $index[$code] ?? null;

        if (empty($byLang)) {
          $skippedNoMatches++;
          $progressBar->advance();
          continue;
        }

        $german  = $byLang['D'] ?? null;
        $french  = $byLang['F'] ?? null;
        $italian = $byLang['I'] ?? null;

        $imageSource = $german ?? $french ?? reset($byLang);
        $altSource   = $german ?? $french ?? reset($byLang);

        $record = [
          'product_id' => $productId,
          'name1_de'   => $german['name1'] ?? '',
          'name2_de'   => $german['name2'] ?? '',
          'text1_de'   => $german['text1'] ?? null,
          'text2_de'   => $german['text2'] ?? null,
          'name1_fr'   => $french['name1'] ?? '',
          'name2_fr'   => $french['name2'] ?? '',
          'text1_fr'   => $french['text1'] ?? null,
          'text2_fr'   => $french['text2'] ?? null,
          'name1_it'   => $italian['name1'] ?? '',
          'name2_it'   => $italian['name2'] ?? '',
          'text1_it'   => $italian['text1'] ?? null,
          'text2_it'   => $italian['text2'] ?? null,
          'image1'     => $imageSource['img1'] ?? null,
          'image2'     => $imageSource['img2'] ?? null,
          'image3'     => $imageSource['img3'] ?? null,
          'image4'     => $imageSource['img4'] ?? null,
          'image5'     => $imageSource['img5'] ?? null,
          'image6'     => $imageSource['img6'] ?? null,
          'alt1'       => $altSource['alt1'] ?? null,
          'alt2'       => $altSource['alt2'] ?? null,
          'alt3'       => $altSource['alt3'] ?? null,
          'alt4'       => $altSource['alt4'] ?? null,
          'alt5'       => $altSource['alt5'] ?? null,
          'alt6'       => $altSource['alt6'] ?? null,
          'updated_at' => now(),
          'created_at' => now(),
        ];

        if (!$sampleLogged) {
          $this->newLine();
          $this->info('Sample data (first item):');
          $this->line('  Product ID: ' . $record['product_id']);
          $this->line('  Name (DE): ' . $record['name1_de']);
          $sampleLogged = true;
        }

        $updateData[] = $record;
        $progressBar->advance();
      }

      if (!empty($updateData)) {
        ProductDescription::upsert($updateData, ['product_id'], $upsertFields);
        $syncedCount += count($updateData);
      }

      unset($updateData);
    }

    // Free the index now that all DB writes are done
    unset($index);

    $progressBar->finish();
    $this->newLine(2);

    $this->info('Statistics:');
    $this->info('  Products in database: ' . $products->count());
    $this->info('  Skipped (no CSV match): ' . $skippedNoMatches);
    $this->info('  Synced: ' . $syncedCount);
    $this->newLine();

    if ($syncedCount > 0) {
      $countAfter = ProductDescription::count();
      $this->info('Successfully synced ' . $syncedCount . ' product descriptions');
      $this->info('  Total rows in product_descriptions table: ' . $countAfter);
    } else {
      $this->warn('No products to sync');
      $this->warn('This likely means:');
      $this->warn('  - CSV has different product codes than database');
      $this->warn('  - All products are missing required languages (D, F, I)');
    }
  }
}
