<?php

namespace Muyki\LaravelExcelTranslations\Services;

use Muyki\LaravelExcelTranslations\Contracts\CacheManagerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TranslationCacheManager implements CacheManagerInterface
{
    protected bool $enabled;
    protected string $keyPrefix;
    protected int $expiration;
    protected ?string $store;

    public function __construct()
    {
        $this->enabled = config('excel-translations.cache.enabled', true);
        $this->keyPrefix = config('excel-translations.cache.key', 'excel_translations');
        $this->expiration = $this->parseExpiration(
            config('excel-translations.cache.expiration_time', 86400)
        );
        $this->store = config('excel-translations.cache.store');
    }

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        if (!$this->enabled) {
            return null;
        }

        try {
            $fullKey = $this->makeKey($key);
            $data = $this->cache()->get($fullKey);

            if ($data !== null) {
                Log::debug('Cache hit', ['key' => $fullKey]);
            }

            return $data;
        } catch (\Exception $e) {
            Log::warning('Cache read failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @inheritDoc
     */
    public function put(string $key, $data, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $fullKey = $this->makeKey($key);
            $ttl = $ttl ?? $this->expiration;

            $this->cache()->put($fullKey, $data, $ttl);

            Log::debug('Cache written', [
                'key' => $fullKey,
                'ttl' => $ttl
            ]);

            return true;
        } catch (\Exception $e) {
            Log::warning('Cache write failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            $fullKey = $this->makeKey($key);
            return $this->cache()->forget($fullKey);
        } catch (\Exception $e) {
            Log::warning('Cache forget failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        try {
            // Bilinen locale'ler için cache'i temizle
            $locales = $this->getKnownLocales();

            foreach ($locales as $locale) {
                $this->forgetByLocale($locale);
            }

            Log::info('Translation cache flushed');
            return true;
        } catch (\Exception $e) {
            Log::warning('Cache flush failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Locale bazlı cache temizle
     */
    public function forgetByLocale(string $locale): bool
    {
        $hash = $this->getFilesHash();
        $key = "{$locale}.{$hash}";
        return $this->forget($key);
    }

    public function makeKey(string $suffix): string
    {
        return "{$this->keyPrefix}.{$suffix}";
    }

    public function getFilesHash(): string
    {
        $path = config('excel-translations.files.path', base_path('lang'));

        if (!File::exists($path)) {
            return 'no-files';
        }

        $files = [];
        $formats = config('excel-translations.files.formats', ['csv', 'xls', 'xlsx']);

        foreach ($formats as $format) {
            $files = array_merge($files, File::glob("{$path}/*.{$format}"));
        }

        $hashData = [];
        $ignorePatterns = config('excel-translations.files.ignore_patterns', ['~$']);

        foreach ($files as $file) {
            $basename = basename($file);

            foreach ($ignorePatterns as $pattern) {
                if (str_contains($basename, $pattern)) {
                    continue 2;
                }
            }

            $hashData[] = $basename . ':' . File::lastModified($file);
        }

        return md5(implode('|', $hashData));
    }

    protected function cache()
    {
        return $this->store ? Cache::store($this->store) : Cache::store();
    }

    protected function parseExpiration($expiration): int
    {
        if (is_numeric($expiration)) {
            return (int) $expiration;
        }

        if (is_string($expiration)) {
            try {
                $interval = \DateInterval::createFromDateString($expiration);
                $now = new \DateTime();
                $future = clone $now;
                $future->add($interval);
                return $future->getTimestamp() - $now->getTimestamp();
            } catch (\Exception $e) {
                return 86400; // Default 24 Hour
            }
        }

        if ($expiration instanceof \DateInterval) {
            $now = new \DateTime();
            $future = clone $now;
            $future->add($expiration);
            return $future->getTimestamp() - $now->getTimestamp();
        }

        return 86400;
    }

    protected function getKnownLocales(): array
    {
        return [
            config('app.locale', 'en'),
            config('app.fallback_locale', 'en'),
            'en', 'tr', 'de', 'fr', 'es'
        ];
    }
}
