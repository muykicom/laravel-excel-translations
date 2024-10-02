<?php
namespace Muyki\LaravelExcelTranslations\Console\Commands;

    use Aws\Translate\TranslateClient;
    use Illuminate\Console\Command;
    use Muyki\LaravelExcelTranslations\Exceptions\TranslationException;
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\Reader\Csv;
    use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
    use PhpOffice\PhpSpreadsheet\Reader\Xls;
    use Illuminate\Support\Facades\File;

    class TranslateExcelTranslations extends Command
    {
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'excel-translations:translate {--target=en}';

        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Translates into other languages with reference to texts in the specified target language.';

        /**
         * Execute the console command.
         *
         * @return int
         */
        public function handle()
        {

            $targetLanguageOption = $this->option('target');
            if (!$targetLanguageOption) {
                $this->error('Please specify the target language with the --target option. Example: --target=tr');
                return 1;
            }

            $sourceLanguage = trim($targetLanguageOption);

            $awsConfig = config('excel_translations.aws');

            $client = new TranslateClient([
                'version'     => $awsConfig['version'],
                'region'      => $awsConfig['region'],
                'credentials' => [
                    'key'    => $awsConfig['key'],
                    'secret' => $awsConfig['secret'],
                ],
            ]);

            $files = array_merge(
                File::glob(base_path('lang/*.csv')),
                File::glob(base_path('lang/*.xls')),
                File::glob(base_path('lang/*.xlsx'))
            );

            foreach ($files as $filePath) {
                $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                $fileName = basename($filePath);

                $this->info("İşleniyor: {$fileName}");

                $reader = match ($extension) {
                    'csv' => IOFactory::createReader('Csv'),
                    'xls' => IOFactory::createReader('Xls'),
                    'xlsx' => IOFactory::createReader('Xlsx'),
                    default => null,
                };

                if (!$reader) {
                    $this->error("Unsupported file format: {$extension}");
                    continue;
                }

                $spreadsheet = $reader->load($filePath);
                $sheet = $spreadsheet->getActiveSheet();

                $headerRow = $sheet->rangeToArray('A1:' . $sheet->getHighestColumn() . '1')[0];

                $languageColumns = [];
                foreach ($headerRow as $index => $header) {
                    $header = trim($header);
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $languageColumns[$header] = $columnLetter;
                }

                if (!isset($languageColumns[$sourceLanguage])) {
                    $this->error("Source language column '{$sourceLanguage}' was not found in file '{$fileName}'.");
                    continue;
                }

                $sourceColumn = $languageColumns[$sourceLanguage];

                $targetLanguages = array_keys($languageColumns);

                $targetLanguages = array_filter($targetLanguages, function ($lang) use ($sourceLanguage) {
                    return $lang !== 'Key' && $lang !== $sourceLanguage;
                });

                $highestRow = $sheet->getHighestRow();
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sourceCell = $sourceColumn . $row;
                    $sourceText = $sheet->getCell($sourceCell)->getValue();

                    if (empty($sourceText)) {
                        continue;
                    }

                    foreach ($targetLanguages as $language) {
                        $language = trim($language);
                        $targetColumn = $languageColumns[$language];
                        $targetCell = $targetColumn . $row;

                        $existingTranslation = $sheet->getCell($targetCell)->getValue();
                        if (empty($existingTranslation)) {
                            try {
                                $result = $client->translateText([
                                    'SourceLanguageCode' => $sourceLanguage,
                                    'TargetLanguageCode' => $language,
                                    'Text' => $sourceText,
                                ]);
                                $translatedText = $result['TranslatedText'];

                                $sheet->setCellValue($targetCell, $translatedText);

                                $this->info("Translate completed: '{$sourceText}' => '{$translatedText}' (Language: '{$language}').");

                            } catch (\Exception $e) {
                                throw new TranslationException(  "Translation error for '{$sourceText}' (Dil: '{$language}'): " . $e->getAwsErrorMessage());
                            }
                        }
                    }
                }

                $writer = match ($extension) {
                    'csv' => IOFactory::createWriter($spreadsheet, 'Csv'),
                    'xls' => IOFactory::createWriter($spreadsheet, 'Xls'),
                    'xlsx' => IOFactory::createWriter($spreadsheet, 'Xlsx'),
                    default => null,
                };

                if (!$writer) {
                    $this->error("Unsupported file format: {$extension}");
                    continue;
                }

                $writer->save($filePath);

                $this->info("File '{$fileName}' updated with new translations.");
            }

            return 0;
        }
    }
