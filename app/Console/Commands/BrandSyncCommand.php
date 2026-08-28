<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class BrandSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:brands';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and update Marke values from Sage';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Updating brands table');

        $src = "ftp://$user:$pass@$server/Labs/marke.csv";
        $brands = $action->handle($src, ',');

        $activeBrands = array_filter($brands, function($brand) {
            return $brand['active'] == 1;
        });

        $data = array_map(function($brand) {
            // Helper function to convert empty strings to null
            $emptyToNull = function($value) {
                return ($value === '' || $value === null) ? null : $value;
            };

            return [
                'code' => $brand['code'],
                'name' => $brand['name'],
                'slug' => Str::slug($brand['name']),
                'hideOnMap' => $brand['hide'] ?? 0,
                'description_en' => $emptyToNull($brand['description_en'] ?? null),
                'description_de' => $emptyToNull($brand['description_de'] ?? null),
                'description_fr' => $emptyToNull($brand['description_fr'] ?? null),
                'description_nl' => $emptyToNull($brand['description_nl'] ?? null),
                'description_pl' => $emptyToNull($brand['description_pl'] ?? null),
                'updated_at' => now(),
            ];
        }, $activeBrands);


        if (!empty($data)) {
            Brand::upsert(
                $data,
                ['code'],
                ['name', 'slug', 'updated_at', 'description_en', 'description_de', 'description_fr', 'description_nl', 'description_pl', 'hideOnMap']
            );

            $this->info('Successfully synced ' . count($data) . ' brands with auto-generated slugs');
        } else {
            $this->warn('No brands to sync');
        }
    }
}
