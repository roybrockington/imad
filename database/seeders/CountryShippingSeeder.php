<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CountryShippingSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $shippingData = [
      ['code' => 'CH', 'name' => 'Switzerland', 'shipping_eur' => 15, 'shipping_gbp' => 23, 'shipping_czk' => 675, 'shipping_pln' => 112],
    ];

    foreach ($shippingData as $data) {
      Country::updateOrCreate(
        ['code' => $data['code']],
        [
          'name' => $data['name'],
          'shipping_eur' => $data['shipping_eur'],
          'shipping_gbp' => $data['shipping_gbp'],
          'shipping_czk' => $data['shipping_czk'],
          'shipping_pln' => $data['shipping_pln'],
        ]
      );
    }

    $this->command->info('Country shipping costs updated successfully!');
  }
}
