<?php

use Muyki\LaravelExcelTranslations\Contracts\TranslationRepositoryInterface;


if (! function_exists('__e')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $key
     * @param  array  $replace
     * @param  string|null  $locale
     * @return string|array|null
     */
    function __e($key = null, $replace = [], $locale = null)
    {
        if (is_null($key)) {
            return $key;
        }

        return e_trans($key, $replace, $locale);
    }

    if (! function_exists('e_trans')) {
        /**
         * Translate the given message.
         *
         * @param  string|null  $key
         * @param  array  $replace
         * @param  string|null  $locale
         * @return \Illuminate\Contracts\Translation\Translator|string|array|null
         */
        function e_trans($key = null, $replace = [], $locale = null)
        {
            if (is_null($key)) {
                return '[MISSING]: null key';
            }

            if(is_string($replace)){
                $locale = $replace;
                $replace = [];
            }

            try {
                $repository = app(TranslationRepositoryInterface::class);
                return $repository->getTranslation($key, $replace, $locale);
            }catch (Exception $e){
                if (config('app.debug')) {
                    return "[ERROR]: {$e->getMessage()}";
                }

                return "[MISSING]: {$key}";
            }
        }
    }
}

if (!function_exists('e_trans_has')) {
    /**
     * @param string $key
     * @param string|null $locale
     * @return bool
     */
    function e_trans_has($key, $locale = null)
    {
        try {
            $repository = app(TranslationRepositoryInterface::class);
            return $repository->has($key, $locale);
        } catch (\Exception $e) {
            return false;
        }
    }
}
