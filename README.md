# laravel-excel-translations

Laravel Excel Translations Package
This package allows you to load and use translation strings from Excel and CSV files in your Laravel application. It supports .csv, .xls and .xlsx file formats.

## Installation

1. **Install with Composer:**
```bash
composer require muyki/laravel-excel-translations
```

2. **Save Service Provider:**

It is automatically discovered for Laravel 5.5 and above. For older versions, add the following line to your config/app.php file:

```
'providers' => [
    // ...
    Muyki\LaravelExcelTranslations\LaravelExcelTranslationServiceProvider::class,
],
```

3. **Publish:**

You can publish the configuration file if you wish:

```bash
php artisan vendor:publish --provider="Muyki\LaravelExcelTranslations\LaravelExcelTranslationServiceProvider" --tag="config"
```

## Usage

1. **Prepare Language Files**:

   Place your translation files in the lang directory. Supported file formats:
    - .csv
    - .xls
    - .xlsx
    
    ### **File Structure Example**:

    | Key     | en      | tr           |
    |:--------|:-------:|-------------:|
    | welcome | Welcome | Hoş geldiniz |
    | goodbye | Goodbye | Güle güle    |

## Using Translations:

Use helper functions to get translations:

```
echo __e('file_name.key'); // Translation in default language
echo __e('file_name.key', [], 'tr'); // Translation in the specified language
```

Example:

```
echo __e('messages.welcome'); // 'Welcome'
echo __e('messages.welcome', [], 'tr'); // 'Hoş geldiniz'
```

## Customization

Caching Settings:

You can configure caching from the **config/excel_translations.php** file.

```
return [
    'cache' => [
    'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    'key' => 'muyki.excel_translations.cache',
    'store' => 'default',
    ],
];
```
