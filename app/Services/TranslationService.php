<?php

namespace App\Services;

use DeepL\Translator;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private Translator $translator;

    private array $languageMap = [
        'en' => 'EN-GB',
        'fr' => 'FR',
        'nl' => 'NL',
        'pl' => 'PL',
    ];

    public function __construct()
    {
        $this->translator = new Translator(env('DEEPL'));
    }

    /**
     * Translate text from German to all supported languages
     *
     * @param string $text The German text to translate
     * @param string $sourceLanguage Source language code (default: 'de')
     * @return array Array with translations: ['en' => '...', 'fr' => '...', 'nl' => '...', 'pl' => '...']
     */
    public function translateToAll(string $text, string $sourceLanguage = 'de'): array
    {
        $translations = [];

        // First translate to English as fallback
        $englishTranslation = $this->translate($text, $sourceLanguage, 'en');
        $translations['en'] = $englishTranslation;

        // Translate to other languages
        foreach ($this->languageMap as $field => $deepLCode) {
            if ($field === 'en') continue; // Already handled

            try {
                $translations[$field] = $this->translate($text, $sourceLanguage, $field);
            } catch (\Exception $e) {
                Log::warning("Translation failed for text to $field, using English as fallback", [
                    'text' => substr($text, 0, 100),
                    'error' => $e->getMessage()
                ]);
                $translations[$field] = $englishTranslation;
            }
        }

        return $translations;
    }

    /**
     * Translate text to a specific language
     *
     * @param string $text Text to translate
     * @param string $sourceLanguage Source language code
     * @param string $targetLanguage Target language code
     * @return string Translated text
     */
    public function translate(string $text, string $sourceLanguage, string $targetLanguage): string
    {
        try {
            $deepLTargetCode = $this->languageMap[$targetLanguage] ?? strtoupper($targetLanguage);

            $result = $this->translator->translateText(
                $text,
                $sourceLanguage,
                $deepLTargetCode
            );

            return $result->text;
        } catch (\Exception $e) {
            Log::error("Translation failed", [
                'source_language' => $sourceLanguage,
                'target_language' => $targetLanguage,
                'text' => substr($text, 0, 100),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Translate career fields from German to all languages
     *
     * @param array $germanFields Array with keys: position_de, tasks_de, profile_de, expectations_de
     * @return array Array with all translated fields
     */
    public function translateCareerFields(array $germanFields): array
    {
        $translations = [];

        $fieldsToTranslate = [
            'position' => $germanFields['position_de'] ?? '',
            'tasks' => $germanFields['tasks_de'] ?? '',
            'profile' => $germanFields['profile_de'] ?? '',
            'expectations' => $germanFields['expectations_de'] ?? '',
        ];

        foreach ($fieldsToTranslate as $fieldName => $germanText) {
            if (empty($germanText)) continue;

            $fieldTranslations = $this->translateToAll($germanText);

            foreach ($fieldTranslations as $lang => $translatedText) {
                $translations["{$fieldName}_{$lang}"] = $translatedText;
            }
        }

        return $translations;
    }
}
