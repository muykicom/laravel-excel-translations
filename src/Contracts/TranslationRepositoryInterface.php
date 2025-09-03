<?php

namespace Muyki\LaravelExcelTranslations\Contracts;

interface TranslationRepositoryInterface
{
    /**
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public function get(string $key, array $replace = [], ?string $locale = null) : string;

    /**
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    public function has(string $key, ?string $locale = null) : bool;

    /**
     * @return array
     */
    public function all() : array;

    /**
     * @return void
     */
    public function refresh() : void;
}
