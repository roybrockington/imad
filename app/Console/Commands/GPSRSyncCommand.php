<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Region;
use App\Models\Supplier;
use Illuminate\Console\Command;

class GPSRSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:gpsr';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync supplier-related addresses for GPSR';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
{
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Updating GPSR supplier table');

        $src = "ftp://$user:$pass@$server/" . env('FEED_SUBFOLDER'). "/" . env('FEED_GPSR');
        $suppliers = $action->handle($src, ',');

        $successCount = 0;
        $errorCount = 0;

        foreach ($suppliers as $index => $supplier) {
            try {
                // Find account or skip if not found
                $region = Region::firstWhere('code', $supplier['region'])->id;

                // Sanitize and validate fields
                $sanitizedData = [
                    'code' => $this->sanitizeField($supplier['code'] ?? ''),
                    'name' => $this->sanitizeField($supplier['name'] ?? ''),
                    'address' => $this->sanitizeField($supplier['address'] ?? ''),
                    'city' => $this->sanitizeField($supplier['city'] ?? ''),
                    'postcode' => $this->sanitizeField($supplier['postcode'] ?? ''),
                    'country' => $this->sanitizeField($supplier['country'] ?? ''),
                    'phone' => $this->sanitizeField($supplier['phone'] ?? ''),
                    'fax' => $this->sanitizeField($supplier['fax'] ?? ''),
                    'web' => $this->sanitizeField($supplier['web'] ?? ''),
                    'email' => $this->sanitizeField($supplier['email'] ?? ''),
                    'region_id' => $this->sanitizeField($region ?? ''),
                ];

                // code, name, address, city, country, postcode, web, email, phone, fax, region_id

                Supplier::updateOrCreate(
                    ['code' => $sanitizedData['code']],
                    $sanitizedData
                );

                $successCount++;
            } catch (\Exception $e) {
                $this->error("Row {$index}: Error processing supplier - " . $e->getMessage());
                $errorCount++;
            }
        }

        $this->info("Success 🚀 - Processed: {$successCount}, Errors: {$errorCount}");
    }

    /**
     * Sanitize field to prevent encoding issues
     */
    private function sanitizeField($field): string
    {
        if (empty($field)) {
            return '';
        }

        // Trim whitespace
        $field = trim($field);

        // Ensure valid UTF-8
        if (!mb_check_encoding($field, 'UTF-8')) {
            $field = mb_convert_encoding($field, 'UTF-8', 'UTF-8');
        }

        return $field;
    }
}
