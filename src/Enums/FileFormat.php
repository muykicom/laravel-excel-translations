<?php

namespace Muyki\LaravelExcelTranslations\Enums;

enum FileFormat: string {
    case CSV = 'csv';
    case XLS = 'xls';
    case XLSX = 'xlsx';

    public function getWriterType(): string
    {
        return match ($this) {
            self::CSV => 'Csv',
            self::XLS => 'Xls',
            self::XLSX => 'Xlsx',
        };   
    }
}


