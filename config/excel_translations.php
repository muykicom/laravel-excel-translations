<?php

return [
    'cache' => [
        'enabled' => env('EXCEL_TRANSLATIONS_CACHE_ENABLED', true),
        'expiration_time' => env('EXCEL_TRANSLATIONS_CACHE_TIME', 86400), // 24 Hour
        'key' => 'excel_translations',
        'store' => env('EXCEL_TRANSLATIONS_CACHE_STORE', null),
    ],

    'files' => [
        'path' => env('EXCEL_TRANSLATIONS_PATH', base_path('lang')),
        'formats' => ['csv', 'xls', 'xlsx'],
        'ignore_patterns' => ['~$', '.tmp'],
    ],

    'translations' => [
        'default_locale' => env('EXCEL_TRANSLATIONS_LOCALE', config('app.locale', 'en')),
        'fallback_locale' => env('EXCEL_TRANSLATIONS_FALLBACK', config('app.fallback_locale', 'en')),
    ],

    'aws' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'version' => 'latest',
    ],
];
