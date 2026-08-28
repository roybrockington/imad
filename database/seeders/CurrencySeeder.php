<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

    DB::table('currencies')->insert($currencies);
  }
}
