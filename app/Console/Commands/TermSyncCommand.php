<?php

namespace App\Console\Commands;

use App\Actions\CsvDecodeAction;
use App\Models\Term;
use Illuminate\Console\Command;
use DeepL\Translator;

class TermSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:terms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync payment terms with Sage';

    /**
     * Execute the console command.
     */
    public function handle(CsvDecodeAction $action)
    {
        $server = env('SSG_SERVER');
        $user = env('SSG_USER');
        $pass = env('SSG_PASS');

        $src = "ftp://$user:$pass@$server/Labs/terms.csv";
        $translator = new Translator(env('DEEPL'));

        $languageMap = [
            'fr' => 'FR',
            'en' => 'EN-GB', // or EN-US
            'nl' => 'NL',
            'pl' => 'PL'
        ];

        $this->info('Updating payment terms table...');
        $terms = $action->handle($src, ',');

        $existingTerms = Term::pluck('code', 'name_de')->flip();

        $termsToInsert = [];

        foreach ($terms as $term) {
            $key = $term['code'] . '|' . $term['name'];

            if (!isset($existingTerms[$key])) {
                $translations = [
                    'code' => $term['code'],
                    'name_de' => $term['name'],
                ];

                // First try to get English translation, as it will be our fallback
                $englishTranslation = null;
                try {
                    $result = $translator->translateText(
                        $term['name'],
                        'de',
                        'EN-GB'
                    );
                    $englishTranslation = $result->text;
                    $translations["name_en"] = $englishTranslation;
                } catch (\Exception $e) {
                    $this->warn("Translation failed for {$term['name']} to English, using German as fallback");
                    $englishTranslation = $term['name'];
                    $translations["name_en"] = $englishTranslation;
                }

                // Translate to other languages, using English as fallback
                foreach ($languageMap as $field => $deepLCode) {
                    if ($field === 'en') continue; // Already handled above

                    try {
                        $result = $translator->translateText(
                            $term['name'],
                            'de',
                            $deepLCode
                        );
                        $translations["name_$field"] = $result->text;
                    } catch (\Exception $e) {
                        $this->warn("Translation failed for {$term['name']} to $field, using English as fallback");
                        $translations["name_$field"] = $englishTranslation;
                    }
                }

                $termsToInsert[] = $translations;
            }
        }

        if (!empty($termsToInsert)) {
            Term::insert($termsToInsert);
            $this->info('Inserted ' . count($termsToInsert) . ' new terms');
        } else {
            $this->info('No new terms to insert');
        }

    }
}
