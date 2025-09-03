<?php

namespace Muyki\LaravelExcelTranslations\Console\Commands;

use Illuminate\Console\Command;
use Muyki\LaravelExcelTranslations\LaravelExcelTranslationRegistrar;

class ClearTranslationCacheCommand extends Command
{
    protected $signature = 'excel-translations:cache-clear {--refresh : Cache is clear and reboot}';
    protected $description = 'Clear Excel translations cache';

    public function handle()
    {
        $this->info('🧹 Clearing translation cache...');

        try {
            $translator = app(LaravelExcelTranslationRegistrar::class);

            $translator->clearCache();

            if ($this->option('refresh')) {
                $this->info('🔄 Reloading translations...');
                $translator->loadTranslationsIfNeeded();
                $this->info('✅ Translations reloaded successfully!');
            }

            $this->info('✅ Translation cache cleared successfully!');


            $this->showStatistics();

            return 0;
        }catch (\Exception $e){
            $this->error('❌ Failed to clear cache: ' . $e->getMessage());
            return 1;
        }
    }

    protected function showStatistics()
    {
        $langPath = base_path('lang');

        if (!file_exists($langPath)) {
            return;
        }

        $csvCount = count(glob($langPath . '/*.csv'));
        $xlsCount = count(glob($langPath . '/*.xls'));
        $xlsxCount = count(glob($langPath . '/*.xlsx'));
        $totalCount = $csvCount + $xlsCount + $xlsxCount;

        $this->table(
            ['File Type', 'Count'],
            [
                ['CSV Files', $csvCount],
                ['XLS Files', $xlsCount],
                ['XLSX Files', $xlsxCount],
                ['Total', $totalCount],
            ]
        );
    }

}
