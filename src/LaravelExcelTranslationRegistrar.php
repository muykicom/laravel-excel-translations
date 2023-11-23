<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LaravelExcelTranslationRegistrar
{
    /**
     * The default locale being used by the translator.
     *
     * @var string
     */
    protected $locale;

    /** @var Repository */
    protected $cache;

    /** @var CacheManager */
    protected $cacheManager;

    /** @var \DateInterval|int */
    public static $cacheExpirationTime;

    /** @var string */
    public static $cacheKey;

    /** @var array */
    protected $data;

    public function __construct(CacheManager $cacheManager)
    {
        $this->data = $this->parseFile();
    }

    public function parseFile () {
        $spreadsheet = IOFactory::load(base_path('lang/translations.xlsx'));
        $sheet = $spreadsheet->getActiveSheet();

        $data = [];

        // Get the highest row and column numbers
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Iterate through each row (except the first one with headers)
        for ($row = 2; $row <= $highestRow; $row++) {
            // Get key from the first column
            $key = $sheet->getCell('A' . $row)->getValue();

            // Iterate through the languages (columns starting from the second column)
            for ($col = 'B'; $col <= $highestColumn; $col++) {
                $language = $sheet->getCell($col . '1')->getValue();
                $translation = $sheet->getCell($col . $row)->getValue();

                // If the language array doesn't exist, create it
                if (!isset($data[$language])) {
                    $data[$language] = [];
                }

                // Set the translation for the corresponding language and key
                $data[$language][$key] = $translation;
            }
        }

        return $data;
    }

    public function initializeCache()
    {
        self::$cacheExpirationTime = config('excel_translations.cache.expiration_time') ?: \DateInterval::createFromDateString('24 hours');

        self::$cacheKey = config('excel_translations.cache.key');

        $this->cache = $this->getCacheStoreFromConfig();
    }

    protected function getCacheStoreFromConfig(): Repository
    {
        $cacheDriver = config('excel_translations.cache.store', 'default');

        if ($cacheDriver === 'default') {
            return $this->cacheManager->store();
        }

        if (! \array_key_exists($cacheDriver, config('cache.stores'))) {
            $cacheDriver = 'array';
        }

        return $this->cacheManager->store($cacheDriver);
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $locale = $locale ?: config('app.locale');

        if (!isset($this->data[$locale])) {
            throw new \Exception("Locale \"$locale\" not found!");
        }

        if (!isset($this->data[$locale][$key])) {
            throw new \Exception("Translation key \"$key\" not found!");
        }

        return $this->data[$locale][$key];
    }
}
