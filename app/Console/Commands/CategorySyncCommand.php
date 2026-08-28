<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Category;
use Illuminate\Console\Command;

class CategorySyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:categories';

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

        $this->info('Updating categories table');

        $src = "ftp://$user:$pass@$server/Labs/webcategories.csv";
        $categories = $action->handle($src, ',');

        foreach ($categories as $category) {
            $parent = Category::where('code', $category['parent'])->first()->id ?? null;
            Category::updateOrCreate(['code' => $category['code']],
                [
                    'code' => $category['code'],
                    'parent_id' => $parent,
                    'name_de' => $category['title_de'],
                    'name_en' => $category['title_en'],
                    'name_nl' => $category['title_nl'],
                    'name_pl' => $category['title_pl'],
                    'name_fr' => $category['title_fr'],
                    'desc_de' => $category['desc_de'],
                    'desc_en' => $category['desc_en'],
                    'desc_nl' => $category['desc_nl'],
                    'desc_pl' => $category['desc_pl'],
                    'desc_fr' => $category['desc_fr'],
                    'img' => $category['img'],
                ]
            );
        }

    }
}
