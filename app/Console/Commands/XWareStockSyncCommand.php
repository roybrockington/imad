<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Product;
use Illuminate\Console\Command;
use App\Models\Xware;

class XWareStockSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:xware';

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

        // provide break info if no flags are selected

        // k

        $this->info('Updating xware table');

        $src = "ftp://$user:$pass@$server/Labs/xware.csv";
        $xwares = $action->handle($src, ',');


        foreach ($xwares as $xware) {

            $parent = Product::where('code', $xware['parent'])->first();

            if ($parent) {

            Xware::updateOrCreate(
                ['code' => $xware['item']], [
                    'product_id' => $parent->id,
                    'stock' => $xware['stock'],
                    'discount' => $xware['discount'],
                    'type' => $xware['type'],
                ]
                );

            }

        }


    }
}
