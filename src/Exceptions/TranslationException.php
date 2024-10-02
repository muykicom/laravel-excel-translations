<?php

namespace Muyki\LaravelExcelTranslations\Exceptions;

use Exception;
use Throwable;
class TranslationException extends \Exception
{

    public function __construct($message = "Translation Error", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}