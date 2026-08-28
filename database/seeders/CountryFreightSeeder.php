<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountryFreightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freightData = [
            ['code' => 'AL', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'AT', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'BA', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'BE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'BG', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'CY', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'CZ', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'DE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'DK', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'EE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'FI', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'GR', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'HR', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'HU', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'IE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'LT', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'LU', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'LV', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'ME', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'MK', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'MT', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'NL', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'NO', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'PL', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'RO', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'RS', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'SE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'SI', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'SK', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'XK', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'FR', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'UK', 'freight_eur' => 33, 'freight_czk' => 891, 'freight_gbp' => 30, 'freight_pln' => 148],
            ['code' => 'PT', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'ES', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'IT', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'US', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'TW', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'CH', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'UA', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'FO', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'AE', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'LI', 'freight_eur' => null, 'freight_czk' => null, 'freight_gbp' => null, 'freight_pln' => null],
            ['code' => 'GE', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'MC', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
            ['code' => 'VA', 'freight_eur' => 30, 'freight_czk' => 810, 'freight_gbp' => 27, 'freight_pln' => 134],
        ];

        foreach ($freightData as $data) {
            DB::table('countries')
                ->where('code', $data['code'])
                ->update([
                    'freight_eur' => $data['freight_eur'],
                    'freight_czk' => $data['freight_czk'],
                    'freight_gbp' => $data['freight_gbp'],
                    'freight_pln' => $data['freight_pln'],
                ]);
        }
    }
}
