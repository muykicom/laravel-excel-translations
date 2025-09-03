<?php

namespace Muyki\LaravelExcelTranslations\Contracts;

interface FileParserInterface
{
    /**
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath) : array;

    /**
     * @param string $extension
     * @return bool
     */
    public function supports(string $extension) : bool;
}
