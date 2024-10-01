<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Support\ServiceProvider;

class LaravelExcelTranslationServiceProvider extends ServiceProvider
{
    public function boot(LaravelExcelTranslationRegistrar $laravelExcelTranslationsRegistrar)
    {
        $this->app->singleton(LaravelExcelTranslationRegistrar::class, function ($app) use ($laravelExcelTranslationsRegistrar) {
            return $laravelExcelTranslationsRegistrar;
        });

        $this->publishes([
            __DIR__.'/../config/excel_translations.php' => config_path('excel_translations.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Muyki\LaravelExcelTranslations\Console\Commands\TranslateExcelTranslations::class,
            ]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/excel_translations.php',
            'excel-translations'
        );
    }
}
