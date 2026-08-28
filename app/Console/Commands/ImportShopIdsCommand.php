<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Product;
use Illuminate\Console\Command;

class ImportShopIdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:shopIds';

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
        $ftpUser = env('SSG_USER');
        $pass = env('SSG_PASS');

        $this->info('Importing 4S "article" ids');

        $src = "ftp://$ftpUser:$pass@$server/Unimog/shopSystemIDs.csv";
        $articles = $action->handle($src, ',');

        $imported = 0;

        foreach ($articles as $article) {
            $product = Product::firstWhere('code', $article['item']);
                if ($product && $article['b2b'] !== "NULL") {
                $product->update([
                    'article' => $article['b2b'],
                ]);
                $imported++;
            }
        }

        $this->info($imported . " redundant IDs successfully imported! 🌈");

    }
}
