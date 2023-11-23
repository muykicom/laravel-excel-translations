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
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/excel_translations.php',
            'excel-translations'
        );
    }
}
