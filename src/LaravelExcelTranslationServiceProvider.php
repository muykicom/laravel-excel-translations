<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Support\ServiceProvider;
use Muyki\LaravelExcelTranslations\Console\Commands\ClearTranslationCacheCommand;
use Muyki\LaravelExcelTranslations\Console\Commands\CreateTranslationFileCommand;
use Muyki\LaravelExcelTranslations\Console\Commands\TranslateExcelTranslations;

class LaravelExcelTranslationServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/excel_translations.php',
            'excel-translations'
        );

        $this->app->singleton(LaravelExcelTranslationRegistrar::class, function ($app) {
            return new LaravelExcelTranslationRegistrar();
        });

        $this->app->alias(
            LaravelExcelTranslationRegistrar::class,
            'excel-translations'
        );
    }

    public function boot(LaravelExcelTranslationRegistrar $laravelExcelTranslationsRegistrar)
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


    protected function loadHelpers()
    {
        $helpersPath = __DIR__.'/helpers.php';

        if (file_exists($helpersPath)) {
            require_once $helpersPath;
        }
    }


}
