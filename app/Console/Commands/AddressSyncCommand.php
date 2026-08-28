<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Account;
use App\Models\Address;
use App\Models\Region;
use Illuminate\Console\Command;

class AddressSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Updating customer address table');

        $src = "ftp://$user:$pass@$server/Labs/" . env('FEED_ADDRESSES');
        $addresses = $action->handle($src, ',');

        $successCount = 0;
        $errorCount = 0;

        foreach ($addresses as $index => $address) {
            try {
                // Find account or skip if not found
                $region = Region::firstWhere('code', $address['region'])->id;
                $account = Account::firstWhere([
                    'code' => $address['kto'],
                    'region_id' => $region,
                ]);

                if (!$account) {
                    $this->warn("Row {$index}: Account not found for code: {$address['kto']}");
                    $errorCount++;
                    continue;
                }

                // Sanitize and validate fields
                $sanitizedData = [
                    'code' => $this->sanitizeField($address['code'] ?? ''),
                    'name1' => $this->sanitizeField($address['name1'] ?? ''),
                    'name2' => $this->sanitizeField($address['name2'] ?? ''),
                    'address1' => $this->sanitizeField($address['address1'] ?? ''),
                    'address2' => $this->sanitizeField($address['address2'] ?? ''),
                    'city' => $this->sanitizeField($address['city'] ?? ''),
                    'postcode' => $this->sanitizeField($address['postcode'] ?? ''),
                    'country' => $this->sanitizeField($address['country'] ?? ''),
                    'tel' => $this->sanitizeField($address['tel'] ?? ''),
                    'email' => $this->sanitizeField($address['email'] ?? ''),
                    'account_id' => $account->id,
                    'invoicing' => $address['invoicing'] ?? 0,
                    'default' => $address['default'] ?? 0,
                ];

                Address::updateOrCreate(
                    ['code' => $sanitizedData['code']],
                    $sanitizedData
                );

                $successCount++;
            } catch (\Exception $e) {
                $this->error("Row {$index}: Error processing address - " . $e->getMessage());
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
