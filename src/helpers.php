<?php

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
                return "Translation key \"$key\" not found!";
            }

            if(is_string($replace)){
                $locale = $replace;
                $replace = [];
            }

            return app(\Muyki\LaravelExcelTranslations\LaravelExcelTranslationRegistrar::class)->get($key, $replace, $locale);

        }
    }
}
