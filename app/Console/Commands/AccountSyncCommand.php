<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Account;
use App\Models\Brand;
use App\Models\Category;
use App\Models\CategoryDiscount;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Discount;
use App\Models\Region;
use App\Models\Term;
use Illuminate\Console\Command;

class AccountSyncCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'sync:accounts {--d|discount} {--c|category} {--a|all}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sync accounts with Sage';

  /**
   * Execute the console command.
   */
  public function handle(CsvDecodeAction $action)
  {
    $server = env('SSG_SERVER');
    $user = env('SSG_USER');
    $pass = env('SSG_PASS');

    // provide break info if no flags are selected

    // k

    $this->info('Updating accounts table');

    $src = "ftp://$user:$pass@$server/Labs/kunden.csv";
    $accounts = $action->handle($src, ',');

    $existingAccounts = Account::whereIn('code', array_column($accounts, 'code'))
      ->get()
      ->keyBy(function ($account) {
        return $account->code . '_' . $account->region_id > 1 ? 2 : 1;
      });

    $toUpdate = [];

    foreach ($accounts as $account) {
      $key = $account['code'] . '_' . $account['region'];
      $existing = $existingAccounts->get($key);

      // Check if exact match exists
      $skip = $existing &&
        $existing->name === $account['name'] &&
        $existing->region_id == Region::firstWhere('code', $account['region'])->id;
      $status = $skip ? 'skip' : ($existing ? 'update' : 'create');

      $this->components->twoColumnDetail(
        $account['code'] . ": " . $account['name'],
        " [" . $status . "]"
      );

      if (!$skip && $account['region'] !== null && $account['region'] !== 'ch') {
        $term = Term::firstWhere('code', $account['term']);
        if ($term == null) {
          $term = Term::create([
            'code' => $account['term'],
            'name_en' => $account['termdesc'],
          ]);
        }

        $toUpdate[] = [
          'code' => $account['code'],
          'name' => $account['name'],
          'freeShipping' => $account['freeship'],
          'insurance' => $account['insurance'] === '' ? 0 : $account['insurance'],
          'region_id' => Region::firstWhere('code', $account['region'])->id,
          'term_id' => $term->id,
          'discount' => 1 - (intval($account['offset'])) / 100,
          'country_id' => Country::firstWhere('code', $account['country'])->id,
          'currency_id' => Currency::firstWhere('code', $account['currency'])->id,
        ];
      }
    }

    if (!empty($toUpdate)) {
      Account::upsert(
        $toUpdate,
        ['code', 'region_id'],  // Unique keys
        ['name', 'discount', 'term_id', 'country_id', 'currency_id', 'insurance', 'freeShipping']    // Columns to update
      );
    }

    // d
    if ($this->option('discount') || $this->option('all')) {
      $this->info('Updating discount table');
      $src = "ftp://$user:$pass@$server/Labs/discount_de.csv";
      $discounts = $action->handle($src, ',');

      $this->info('Pre-loading accounts and brands...');
      $accounts = Account::select('id', 'code', 'region_id')
        ->get()
        ->mapWithKeys(fn($account) => ["{$account->code}_{$account->region_id}" => $account->id]);

      $brands = Brand::pluck('id', 'code');

      $this->info('Processing discounts...');
      $bar = $this->output->createProgressBar(count($discounts));
      $data = [];

      foreach ($discounts as $discount) {
        $region = $discount['region'] > 1 ? 2 : 1; // Sanitize any 4 or 5 PLN or CZK keys
        $accountKey = "{$discount['customer']}_{$region}";

        if (isset($accounts[$accountKey]) && isset($brands[$discount['brand']])) {
          $data[] = [
            'account_id' => $accounts[$accountKey],
            'brand_id' => $brands[$discount['brand']],
            'discount' => $discount['discount'],
            'auth' => $discount['auth'],
          ];
        }
        $bar->advance();
      }

      $bar->finish();
      $this->newLine();

      $this->info('Inserting discounts...');
      Discount::truncate();

      if (!empty($data)) {
        foreach (array_chunk($data, 1000) as $chunk) {
          Discount::insert($chunk);
        }
      }

      $this->info('Discounts updated successfully');
    }

    // c
    if ($this->option('category') || $this->option('all')) {

      $this->info('Rebuilding category discount table');

      $src = "ftp://$user:$pass@$server/Labs/" . env('FEED_CATEGORY_DISCOUNTS');

      CategoryDiscount::truncate();

      $categories = $action->handle($src, ',');

      foreach ($categories as $category) {

        $region = Region::where('code', $category['region'])->first()->id;

        if (Account::where('code', $category['customer'])
          ->where('region_id', $region)
          ->first() && Category::where('code', $category['group'])
          ->first()
        ) {
          CategoryDiscount::updateOrCreate(
            ['account_id' => Account::where('code', $category['customer'])
              ->where('region_id', $region)
              ->first()
              ->id, 'category_id' => Category::where('code', $category['group'])->first()->id],
            ['discount' => $category['discount'], 'auth' => $category['auth'], 'brand_id' => Brand::where('code', $category['brand'])->first()->id]
          );
        }
      }
    }
  }
}
