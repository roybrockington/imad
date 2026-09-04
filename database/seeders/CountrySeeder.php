<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $countries = [
      ['code' => 'CH', 'name' => 'Switzerland'],
      ['code' => 'JP', 'name' => 'Japanese'],
      ['code' => 'GB', 'name' => 'United Kingdom'],
    ];

    foreach ($countries as $country) {
      Country::firstOrCreate([
        'code' => $country['code']
      ], [
        'name' => $country['name'],
      ]);
    }
  }
}
