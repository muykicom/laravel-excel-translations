<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Support\Facades\Cache;
use Muyki\LaravelExcelTranslations\Exceptions\FileNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\LocaleNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\TranslationKeyNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\UnsupportedFileFormatException;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Illuminate\Support\Facades\Log;



class LaravelExcelTranslationRegistrar
{

    protected $translations = null;

    protected $locale;


    protected $cacheEnabled;
    protected $cacheKey;
    protected $cacheExpiration;


    public function __construct()
    {
       // Cache get config
       $this->cacheEnabled = config('laravel-excel-translations.cache_enabled', true);
       $this->cacheKey = config('laravel-excel-translations.cache_key', 'excel-translations');
       $this->cacheExpiration = config('laravel-excel-translations.cache.expiration_time',86400 ); // 24 Hour
    }

    public function loadTranslationsIfNeeded()
    {
        if ($this->translations !== null) {
            return;
        }

        if ($this->cacheEnabled){
            $this->translations = $this->getFromCache();

            if ($this->translations !== null) {
                return;
            }
        }

        $this->translations = $this->parseFiles();

        if ($this->cacheEnabled && $this->translations !== null) {
            $this->putToCache($this->translations);
        }
    }

    protected function getFromCache()
    {
        try {
            $locale = config('app.locale','en');
            $cacheKey = $this->getCacheKey($locale);

            $data = Cache::get($cacheKey);

            if ($data !== null) {
                Log::debug('Translations loaded from cache', [
                    'key' => $cacheKey
                ]);
            }

            return $data;
        }catch (\Exception $e){
            Log::warning('Cache Read Failed.',[
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function putToCache($data)
    {
        try {
            $locale = config('app.locale','en');
            $cacheKey = $this->getCacheKey($locale);

            $expiration = $this->getCacheExpration();

            Cache::put($cacheKey, $data, $expiration);

            Log::debug('Translations saved to cache', [
                'key' => $cacheKey,
                'expiration' => $expiration
            ]);

        }catch (\Exception $e){
            Log::warning('Cache write failed', [
                'error' => $e->getMessage()
            ]);
        }
    }


    protected function getCacheKey($locale = null)
    {
        $locale = $locale ?: config('app.locale','en');

        $filesHash = $this->getFilesHash();

        return sprintf('%s.%s.%s',
            $this->cacheKey,
            $locale,
            $filesHash
        );
    }

    protected function getFilesHash()
    {
        $langPath = base_path('lang');

        if(!File::exists($langPath)){
            return 'no-files';
        }

        $files = array_merge(
            File::glob($langPath . '/*.csv'),
            File::glob($langPath . '/*.xls'),
            File::glob($langPath . '/*.xlsx')
        );

        $hashData = [];
        foreach ($files as $file) {

            if (str_contains(basename($file), '~$')){
                continue;
            }

            $hashData[] = basename($file) . ':' . File::lastModified($file);
        }

        return md5(implode('|', $hashData));

    }

    protected function getCacheExpration()
    {
        $expiration = $this->cacheExpiration;

        if(is_string($expiration)){
            try {
                $interval =  \DateInterval::createFromDateString($expiration);
                $now = new \DateTime();
                $future = clone $now;
                $future->add($interval);
                $expiration = $future->getTimestamp() - $now->getTimestamp();
            } catch (\Exception $e){
                return 86400; // Default 24 Hour
            }
        }

        return (int) $expiration;
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

    public function clearCache()
    {
        $this->translations = null;

        if($this->cacheEnabled){
            try {
                $pattern = $this->cacheKey . '.*';
                Cache::forget($pattern);

                Log::info('Translations cleared from cache');
            }catch (\Exception $e){
                Log::warning('Cache clear failed', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function refresh()
    {
        $this->clearCache();
        $this->loadTranslationsIfNeeded();
    }

}
