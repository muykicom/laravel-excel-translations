<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\Repository;
use Muyki\LaravelExcelTranslations\Exceptions\FileNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\LocaleNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\TranslationKeyNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\UnsupportedFileFormatException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Illuminate\Support\Facades\Log;



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

    protected $translations = null;

    public function __construct(CacheManager $cacheManager)
    {
       // $this->parseFiles();
    }

    protected function loadTranslationsIfNeeded()
    {
        if ($this->translations === null) {
            $this->translations = $this->parseFiles();
        }
    }

    public function parseFiles()
    {
        $data = [];

        $langPath = base_path('lang');

        if (!File::exists($langPath)) {
            return $data;
        }

        $files = array_merge(
            File::glob(base_path('lang/*.csv')),
            File::glob(base_path('lang/*.xls')),
            File::glob(base_path('lang/*.xlsx'))
        );

        foreach ($files as $filePath) {

            //temprory file skip
            if (str_contains(basename($filePath), '~$'))
            {
                continue;
            }

            $fileName = pathinfo($filePath, PATHINFO_FILENAME);

            try {
                $data[$fileName] = $this->parseFile($filePath);
            } catch (\Throwable $e) {
                Log::warning("Translation file could not be parsed: {$filePath}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $data;
    }

    public function parseFile ($filePath)
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $reader = match ($extension) {
            'csv' => new Csv(),
            'xls' => new Xls(),
            'xlsx' => new Xlsx(),
            default => throw new UnsupportedFileFormatException($extension),
        };

        $spreadsheet =  $reader->load($filePath);

        $sheet = $spreadsheet->getActiveSheet();

        $data = [];

        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $languages = [];

        for ($col = 'B'; $col <= $highestColumn; $col++) {
            $language = $sheet->getCell($col . '1')->getValue();

            if(!empty($language))
            {
                $languages[$col] = trim($language);
            }
        }

        for($row = 2; $row <= $highestRow; $row++)
        {
            $key = $sheet->getCell('A' . $row)->getValue();

            if(empty($key)){
                continue;
            }

            $key = trim($key);

            foreach($languages as $col => $language){
                $translation = $sheet->getCell($col . $row)->getValue();

                if(!isset($data[$language])) {
                    $data[$language] = [];
                }

                $data[$language][$key] = $translation ?? '';
            }

        }

        return $data;
    }

    public function get($key, $replace = [], $locale = null)
    {

        $this->loadTranslationsIfNeeded();

        $locale = $locale ?: config('app.locale','en');

        $parts = explode('.', $key, 2);

        if(count($parts) !== 2) {
            throw new TranslationKeyNotFoundException($key);
        }

        [$file, $translationKey] = $parts;

        if (!isset($this->translations[$file])) {
            throw new FileNotFoundException($file);
        }

        if(!isset($this->translations[$file][$locale])){
            $fallbackLocale = config('app.fallback_locale','en');

            if (!isset($this->translations[$file][$fallbackLocale])) {
                throw new LocaleNotFoundException($locale);
            }

            $locale = $fallbackLocale;
        }

        if (!isset($this->translations[$file][$locale][$translationKey])) {
            throw new TranslationKeyNotFoundException($translationKey);
        }

        $translation = $this->translations[$file][$locale][$translationKey];

        if (!empty($replace) && is_array($replace)) {
            foreach ($replace as $replaceKey => $value) {
                $translation = str_replace(
                    [':' . $replaceKey, ':' . strtoupper($replaceKey), ':' . ucfirst($replaceKey)],
                    [$value, strtoupper($value), ucfirst($value)],
                    $translation
                );
            }
        }

        return $translation;
    }

    public function refresh()
    {
        $this->translations = null;
        $this->loadTranslationsIfNeeded();
    }

}
