<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Brand;
use App\Models\Country;
use Illuminate\Console\Command;

class CountrySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:countries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync brand-country access from CSV feed';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Rebuilding country/brand access pivot');

        $src = "ftp://$user:$pass@$server/".env('FEED_SUBFOLDER')."/".env('FEED_ACCESS');

        $this->info('Loading CSV data...');
        $brandAccess = $action->handle($src, ',');

        // Load all brands and countries into memory for faster lookups
        $brands = Brand::pluck('id', 'code');
        $countries = Country::pluck('id', 'code');

        $this->info('Processing ' . count($brandAccess) . ' access records...');

        // Build array of brand-country relationships
        // Group by brand to minimize database queries
        $brandCountryMap = [];

        foreach ($brandAccess as $access) {
            $brandCode = $access['brand_code'] ?? null;
            $countryCode = $access['country_code'] ?? null;

            $authorised = $access['authorised'] ?? null;

            if (!$brandCode || !$countryCode) {
                continue;
            }

            // Skip rows where the feed explicitly marks access as unauthorised
            if (filter_var($authorised, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) {
                continue;
            }

            $brandId = $brands->get($brandCode);
            $countryId = $countries->get($countryCode);

            if (!$brandId || !$countryId) {
                $this->warn("Skipping: Brand '{$brandCode}' or Country '{$countryCode}' not found");
                continue;
            }

            // Group countries by brand
            if (!isset($brandCountryMap[$brandId])) {
                $brandCountryMap[$brandId] = [];
            }
            $brandCountryMap[$brandId][] = $countryId;
        }

        $this->info('Syncing ' . count($brandCountryMap) . ' brands...');

        $progressBar = $this->output->createProgressBar(count($brandCountryMap));
        $progressBar->start();

        $totalRelationships = 0;

        // Sync each brand's authorized countries
        foreach ($brandCountryMap as $brandId => $countryIds) {
            $brand = Brand::find($brandId);
            if ($brand) {
                $brand->countries()->sync($countryIds);
                $totalRelationships += count($countryIds);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Synced {$totalRelationships} brand-country relationships");
        $this->info('Done!');
    }
}
