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

    $src = "ftp://$user:$pass@$server/" . env('FEED_SUBFOLDER') . "/kategorien.csv";
    $categories = $action->handle($src, ',');

    foreach ($categories as $category) {
      $parent = Category::where('code', $category['parent'])->first()->id ?? null;
      Category::updateOrCreate(
        ['code' => $category['code']],
        [
          'code' => $category['code'],
          'parent_id' => $parent,
          'name_de' => $category['title_de'],
          'name_fr' => $category['title_fr'],
          'name_it' => $category['title_it'],
          'desc_de' => $category['desc_de'],
          'desc_it' => $category['desc_it'],
          'desc_fr' => $category['desc_fr'],
          'img' => $category['img'],
        ]
      );
    }
  }
}
