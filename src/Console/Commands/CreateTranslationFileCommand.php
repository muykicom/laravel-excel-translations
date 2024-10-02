<?php

namespace Muyki\LaravelExcelTranslations\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use Illuminate\Support\Facades\Log;
use Muyki\LaravelExcelTranslations\Enums\FileFormat;
use Illuminate\Support\Facades\File;

class CreateTranslationFileCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'excel-translations:create {filename?} {--format=} {--languages=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new translation file with the given format and languages.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() : int
    {
        $fileName = $this->argument('filename') ?: $this->ask('Enter the file name');

        $formatOption = $this->option('format');
        $formats = array_map(fn($format) => $format->value, FileFormat::cases());
        
        if ($formatOption && in_array($formatOption, $formats, true)) {
            $format = FileFormat::from($formatOption);
        } else {
            $formatValue = $this->choice('Select the file format', $formats,0);
            $format = FileFormat::from($formatValue);
        }
        
        $languageOption = $this->option('languages');

        if ($languageOption) {
            $languages = array_map('trim', explode(',', $languageOption));
        } else {
            $languagesInput = $this->ask('Enter the languages (comma-separated)');
            $languages = array_map('trim', explode(',', $languagesInput));
        }

        if (empty($languages)) {
            $this->error('Please specify at least one language.');
            return 1;
        }

        if (!File::exists(base_path('lang'))) {
            $this->info('Lang directory not found, creating...');
            $this->call('lang:publish');
        }

        $path = base_path("lang/{$fileName}.{$format->value}");

        // Dosya zaten mevcut mu kontrol et
        if (file_exists($path)) {
            if (!$this->confirm("The file {$fileName}.{$format->value} already exist. Do you want to overwrite it?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Adding data to the spreadsheet
        $sheet->setCellValue('A1', 'Key');
        
        foreach ($languages as $index => $language) {
            $sheet->setCellValueByColumnAndRow($index + 2, 1, $language);
        }

        $writer = match ($format->value) {
            'csv' => IOFactory::createWriter($spreadsheet, 'Csv'),
            'xls' => IOFactory::createWriter($spreadsheet, 'Xls'),
            'xlsx' => IOFactory::createWriter($spreadsheet, 'Xlsx'),
            default => null,
        };

        if (!$writer) {
            $this->error('Unsupported file format.');
            return 1;
        }

        try {
            $writer->save($path);
        } catch (WriterException $e) {
            Log::error("Error saving Excel file: " . $e->getMessage());
            $this->error("The file could not be saved. Please make sure that the file is not open in another programme and that you have the necessary permissions.");
            return 1;
        } catch (\Exception $e) {
            Log::error("Beklenmeyen hata: " . $e->getMessage());
            $this->error("An unexpected error occurred: " . $e->getMessage());
            return 1;
        }

        $this->info("Translation file {$fileName}.{$format->value} has been created.");
        return 0;
    }
}
