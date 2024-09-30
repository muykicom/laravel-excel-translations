<?php

namespace Muyki\LaravelExcelTranslations\Exceptions;

use Exception;
use Throwable;
class TranslationKeyNotFoundException extends \Exception
{
    protected string $translationKey;

    public function __construct(string $translationKey, $message = null, $code = 404, Throwable $previous = null)
    {
        $this->translationKey = $translationKey;
        $message = $message ?? "Translation key '$translationKey' not found.";
        parent::__construct($message, $code, $previous);
    }

    public function getTranslationKey(): string
    {
        return $this->translationKey;
    }

    public function report()
    {
        \Log::error("Translation key not found : " . $this->translationKey);
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'Translation key not found ',
            'key' => $this->translationKey,
        ], 404);
    }



}