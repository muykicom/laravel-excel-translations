<?php

namespace Muyki\LaravelExcelTranslations\Services;

use Illuminate\Support\Facades\Log;
use Muyki\LaravelExcelTranslations\Contracts\FileParserInterface;
use Muyki\LaravelExcelTranslations\Exceptions\FileNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\UnsupportedFileFormatException;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Illuminate\Support\Facades\File;

class FileParser implements FileParserInterface
{
    protected  array $supportedFormats;
    protected  array $ignorePatterns;

    public function __construct()
    {
        $this->supportedFormats = config('excel-translations.files.formats', ['csv', 'xls', 'xlsx']);
        $this->ignorePatterns = config('excel-translations.files.ignore_patterns', ['~$', '.tmp']);
    }

    /**
     * @throws UnsupportedFileFormatException
     * @throws FileNotFoundException
     * @throws Exception
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new FileNotFoundException($filePath);
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if(!$this->supports($extension)) {
            throw new UnsupportedFileFormatException($filePath);
        }

        $reader  = $this->getReader($extension);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        return $this->extractTranslations($sheet);
    }

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), $this->supportedFormats);
    }

    public function parseAll(string $directory) : array
    {
        if (!File::exists($directory)) {
            return [];
        }

        $files = $this->getTranslationFiles($directory);
        $translations = [];


        foreach ($files as $filePath) {
            if ($this->shouldIgnore($filePath)) {
                continue;
            }

            $fileName = pathinfo($filePath, PATHINFO_FILENAME);

            try {
                $translations[$fileName] = $this->parse($filePath);
            } catch (\Exception $e) {
                Log::warning("Failed to parse translation file: $filePath", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $translations;
    }

    /**
     * @throws UnsupportedFileFormatException
     */
    protected function getReader(string $extension): Xls|Csv|Xlsx
    {
        return match ($extension) {
            'csv' => $this->createCsvReader(),
            'xls' => new Xls(),
            'xlsx' => new Xlsx(),
            default => throw new UnsupportedFileFormatException($extension)
        };
    }

    protected function createCsvReader(): Csv
    {
        $reader = new Csv();
        $reader->setDelimiter(',');
        $reader->setEnclosure('"');
        $reader->setSheetIndex(0);
        return $reader;
    }

    protected function extractTranslations($sheet): array
    {
        $data = [];
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $languages = $this->extractLanguages($sheet, $highestColumn);

        for ($row = 2; $row <= $highestRow; $row++) {
            $key = trim($sheet->getCell('A' . $row)->getValue() ?? '');

            if (empty($key)) {
                continue;
            }

            foreach ($languages as $col => $language) {
                $translation = $sheet->getCell($col . $row)->getValue();

                if (!isset($data[$language])) {
                    $data[$language] = [];
                }

                $data[$language][$key] = $translation ?? '';
            }
        }

        return $data;
    }

    protected function extractLanguages($sheet, string $highestColumn): array
    {
        $languages = [];

        for ($col = 'B'; $col <= $highestColumn; $col++) {
            $language = trim($sheet->getCell($col . '1')->getValue() ?? '');

            if (!empty($language)) {
                $languages[$col] = $language;
            }
        }

        return $languages;
    }

    protected function getTranslationFiles(string $directory): array
    {
        $files = [];

        foreach ($this->supportedFormats as $format) {
            $files = array_merge($files, File::glob("$directory/*.$format"));
        }

        return $files;
    }


    protected function shouldIgnore(string $filePath): bool
    {
        $basename = basename($filePath);

        foreach ($this->ignorePatterns as $pattern) {
            if (str_contains($basename, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
