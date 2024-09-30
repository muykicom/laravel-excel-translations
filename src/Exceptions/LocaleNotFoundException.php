<?php

namespace Muyki\LaravelExcelTranslations\Exceptions;

use Exception;
use Throwable;
class LocaleNotFoundException extends \Exception
{

    protected string $locale;

    public function __construct(string $locale,string $message = null, int $code = 404, Throwable $previous = null)
    {
        $this->locale = $locale;
        $message = $message ?? "Language '$locale' not found.";
        parent::__construct($message, $code, $previous);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function report()
    {
        \Log::error("Language Not Found: " . $this->locale);
    }

    public function render($request)
    {
        return response()->json([
            'error' => 'Language Not Found',
            'locale' => $this->locale,
            'available_locales' => config('app.locales', ['en', 'tr'])
        ], 404);
    }

}