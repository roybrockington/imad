<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $currencies = [
      ['code' => 'CHF', 'name' => 'Swiss Franc'],
    ];

    foreach ($currencies as $currency) {
      Currency::firstOrCreate(
        [
          'code' => $currency['code']
        ],
        ['name' => $currency['name']]
      );
    }
  }
}
