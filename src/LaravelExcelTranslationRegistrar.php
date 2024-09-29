<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\Repository;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;


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
        $this->parseFiles();
    }

    public function parseFiles() {
        $data = [];

        $files = array_merge(
            File::glob(base_path('lang/*.csv')),
            File::glob(base_path('lang/*.xls')),
            File::glob(base_path('lang/*.xlsx'))
        );

        $fileNames = array_map(function ($file) {
            return ["file" => basename($file), "key" => pathinfo($file, PATHINFO_FILENAME)];
        }, $files);

        foreach ($fileNames as $f) {
            if (!str_contains($f['file'], '~$')){
                $data[$f['key']] = $this->parseFile($f['file']);
            }
        }

        $this->data = $data;
    }

    public function parseFile ($file) {

        $filePath = base_path('lang/' . $file);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        $reader = match ($extension) {
            'csv' => new Csv(),   
            'xls' => new Xls(),
            'xlsx' => new Xlsx(),
            default => throw new \Exception('Unsupported file format'),
        };

        $spreadsheet =  $reader->load($filePath); 
        
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

    public function get($key, $locale = null) {
        $locale = $locale ?: config('app.locale');

        [$file, $key] = explode(".", $key, 2);

        if (!isset($this->data[$file])) {
            throw new \Exception("File \"$file\" not found!");
        }

        if (!isset($this->data[$file][$locale])) {
            throw new \Exception("Locale \"$locale\" not found!");
        }

        if (!isset($this->data[$file][$locale][$key])) {
            throw new \Exception("Translation key \"$key\" not found!");
        }

        return $this->data[$file][$locale][$key];
    }
}
