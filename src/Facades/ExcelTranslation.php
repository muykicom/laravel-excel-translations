<?php
namespace Muyki\LaravelExcelTranslations\Facades;

use Illuminate\Support\Facades\Facade;

class ExcelTranslation extends Facade
{

    /**
     * @method static string get(string $key)
     */
    protected static function getFacadeAccessor()
    {
        return 'excel-translations';
    }
}
