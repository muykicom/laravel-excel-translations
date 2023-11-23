<?php

namespace Muyki\LaravelExcelTranslations;

use Illuminate\Cache\CacheManager;
use Illuminate\Support\Str;
use Illuminate\Contracts\Cache\Repository;
use InvalidArgumentException;
class LaravelExcelTranslationRegistrar
{
    /**
     * The default locale being used by the translator.
     *
     * @var string
     */
    protected $locale;

    /** @var Repository */
    protected $cache;

    /** @var CacheManager */
    protected $cacheManager;

    /** @var string */
    public static $cacheKey;

    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;

        $this->setLocale(config('app.locale'));

        $this->initializeCache();
    }

    public function initializeCache()
    {
        self::$cacheExpirationTime = config('excel_translations.cache.expiration_time') ?: \DateInterval::createFromDateString('24 hours');

        self::$cacheKey = config('excel_translations.cache.key');

        $this->cache = $this->getCacheStoreFromConfig();
    }

    protected function getCacheStoreFromConfig(): Repository
    {
        $cacheDriver = config('excel_translations.cache.store', 'default');

        if ($cacheDriver === 'default') {
            return $this->cacheManager->store();
        }

        if (! \array_key_exists($cacheDriver, config('cache.stores'))) {
            $cacheDriver = 'array';
        }

        return $this->cacheManager->store($cacheDriver);
    }

    public function setLocale($locale)
    {
        if (Str::contains($locale, ['/', '\\'])) {
            throw new InvalidArgumentException('Invalid characters present in locale.');
        }

        $this->locale = $locale;
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        return $locale ?: $this->locale;
    }
}
