<?php

namespace Muyki\LaravelExcelTranslations\Exceptions;

use Exception;
use Throwable;
class FileNotFoundException extends \Exception
{
    public function __construct(string $filePath, $message = null, $code = 404, Throwable $previous = null)
    {
        $this->filePath = $filePath;
        $message = $message ?? "File Not Found : '$filePath' ";
        parent::__construct($message, $code, $previous);
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function report()
    {
        \Log::error("File Not Found: " . $this->filePath);
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'File Not Found',
            'file' => $this->filePath
        ], 404);
    }
}