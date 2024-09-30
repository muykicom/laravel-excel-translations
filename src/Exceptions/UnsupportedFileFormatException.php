<?php

namespace Muyki\LaravelExcelTranslations\Exceptions;

use Exception;
use Throwable;
class UnsupportedFileFormatException extends \Exception
{
    protected string $fileFormat;

    public function __construct(string $fileFormat, $message = null, $code = 400, Throwable $previous = null)
    {
        $this->fileFormat = $fileFormat;
        $message = $message ?? "File format '$fileFormat' is not supported.";
        parent::__construct($message, $code, $previous);
    }

    public function getFileFormat(): string
    {
        return $this->fileFormat;
    }

    public function report()
    {
        \Log::error("Unsupported file format: " . $this->fileFormat);
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'Unsupported file format',
            'file_format' => $this->fileFormat
        ], 400);
    }
}