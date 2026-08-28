<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Country;
use Illuminate\Console\Command;

class ImportCarriageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:carriage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update country carriage manually - required csv columns [code], [shipping_eur], [shipping_gbp], [shipping_pln], [shipping_czk]';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Updating carriage table');

        $count = 0;

        $src = "ftp://$user:$pass@$server/Unimog/newShipping.csv";
        $charges = $action->handle($src, ',');

        foreach ($charges as $charge) {
            Country::firstWhere('code', $charge['code'])->update([
                    'shipping_eur' => (float) $charge['shipping_eur'] ?? null,
                    'shipping_pln' => (float) $charge['shipping_pln'] ?? null,
                    'shipping_czk' => (float) $charge['shipping_czk'] ?? null,
                    'shipping_gbp' => (float) $charge['shipping_gbp'] ?? null,
            ]);
            $count++;
        }

        $this->info($count . ' shipping rows updated');
    }
}
