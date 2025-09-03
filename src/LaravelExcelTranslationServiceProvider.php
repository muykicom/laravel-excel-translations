<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Support\ServiceProvider;
use Muyki\LaravelExcelTranslations\Console\Commands\ClearTranslationCacheCommand;
use Muyki\LaravelExcelTranslations\Console\Commands\CreateTranslationFileCommand;
use Muyki\LaravelExcelTranslations\Contracts\CacheManagerInterface;
use Muyki\LaravelExcelTranslations\Contracts\FileParserInterface;
use Muyki\LaravelExcelTranslations\Contracts\TranslationRepositoryInterface;
use Muyki\LaravelExcelTranslations\Repositories\TranslationRepository;
use Muyki\LaravelExcelTranslations\Services\FileParser;
use Muyki\LaravelExcelTranslations\Services\TranslationCacheManager;

class LaravelExcelTranslationServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/excel_translations.php',
            'excel-translations'
        );

        $this->app->bind(FileParserInterface::class, FileParser::class);

        $this->app->singleton(CacheManagerInterface::class, TranslationCacheManager::class);

        $this->app->singleton(TranslationRepositoryInterface::class, function ($app) {
            return new TranslationRepository(
                $app->make(FileParserInterface::class),
                $app->make(CacheManagerInterface::class)
            );
        });

        // Alias for facade
        $this->app->alias(TranslationRepositoryInterface::class, 'excel-translations');

        $this->app->bind(LaravelExcelTranslationRegistrar::class, function ($app) {
            return new LaravelExcelTranslationRegistrar();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/excel_translations.php' => config_path('excel_translations.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                // TranslateExcelTranslations::class,
                CreateTranslationFileCommand::class,
                ClearTranslationCacheCommand::class,
            ]);
        }

        $this->loadHelpers();
    }


    protected function loadHelpers() : void
    {
        $helpersPath = __DIR__.'/helpers.php';

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }

    public function provides() : array
    {
        return [
            FileParserInterface::class,
            CacheManagerInterface::class,
            TranslationRepositoryInterface::class,
            'excel-translations',
        ];
    }


}
