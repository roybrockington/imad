<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call(RoleSeeder::class);
    $this->call(RegionSeeder::class);
    $this->call(UserSeeder::class);
    $this->call(SlideSeeder::class);
    $this->call(CurrencySeeder::class);
    $this->call(CountrySeeder::class);
    $this->call(CountryShippingSeeder::class);

    Artisan::call('sync:brands');
    Artisan::call('sync:categories');
    Artisan::call('sync:products');

    Artisan::call('sync:terms');
    Artisan::call('sync:accounts -dc');

    $this->call(BrandSlugSeeder::class);
  }
}
