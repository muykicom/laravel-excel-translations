<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Support\Facades\Cache;
use Muyki\LaravelExcelTranslations\Contracts\FileParserInterface;
use Muyki\LaravelExcelTranslations\Contracts\TranslationRepositoryInterface;
use Muyki\LaravelExcelTranslations\Exceptions\FileNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\LocaleNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\TranslationKeyNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\UnsupportedFileFormatException;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Illuminate\Support\Facades\Log;



class LaravelExcelTranslationRegistrar
{

    protected TranslationRepositoryInterface $repository;

    public function __construct()
    {
        $this->repository = app(TranslationRepositoryInterface::class);
    }

    public function loadTranslationsIfNeeded()
    {
       // use repository
       return;
    }

    public function parseFiles()
    {
        return $this->repository->all();
    }

    public function parseFile ($filePath)
    {
        $parser = app(FileParserInterface::class);
        return $parser->parse($filePath);
    }

    public function get($key, $replace = [], $locale = null)
    {
        return $this->repository->get($key, $replace, $locale);
    }

    public function clearCache()
    {
        $this->repository->refresh();
    }

    public function refresh()
    {
        $this->repository->refresh();
    }

}
