<?php

namespace Muyki\LaravelExcelTranslations\Repositories;

use Muyki\LaravelExcelTranslations\Contracts\TranslationRepositoryInterface;
use Muyki\LaravelExcelTranslations\Contracts\FileParserInterface;
use Muyki\LaravelExcelTranslations\Contracts\CacheManagerInterface;
use Muyki\LaravelExcelTranslations\Exceptions\FileNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\LocaleNotFoundException;
use Muyki\LaravelExcelTranslations\Exceptions\TranslationKeyNotFoundException;

class TranslationRepository implements TranslationRepositoryInterface
{

    protected ?array $translations = null;

    protected FileParserInterface $fileParser;
    protected CacheManagerInterface $cacheManager;

    protected string $defaultLocale;
    protected string $fallbackLocale;
    protected string $translationsPath;

    public function __construct(
        FileParserInterface $fileParser,
        CacheManagerInterface $cacheManager
    ) {
        $this->fileParser = $fileParser;
        $this->cacheManager = $cacheManager;

        $this->defaultLocale = config('excel-translations.translations.default_locale', 'en');
        $this->fallbackLocale = config('excel-translations.translations.fallback_locale', 'en');
        $this->translationsPath = config('excel-translations.files.path', base_path('lang'));
    }
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $this->loadTranslations();

        $locale = $locale ?? $this->defaultLocale;

        [$file, $translationKey] = $this->parseKey($key);

        if (!isset($this->translations[$file])) {
            throw new FileNotFoundException($file);
        }

        $translation = $this->findTranslation($file, $translationKey, $locale);

        return $this->makeReplacements($translation, $replace);
    }


    public function has(string $key, ?string $locale = null): bool
    {
        try {
            $this->get($key, [], $locale);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function all(): array
    {
        $this->loadTranslations();
        return $this->translations ?? [];
    }


    public function refresh(): void
    {
        $this->translations = null;
        $this->cacheManager->flush();
        $this->loadTranslations();
    }


    protected function loadTranslations(): void
    {
        // Memory cache kontrolü
        if ($this->translations !== null) {
            return;
        }

        $locale = $this->defaultLocale;
        $hash = $this->cacheManager->getFilesHash();
        $cacheKey = "{$locale}.{$hash}";

        $this->translations = $this->cacheManager->get($cacheKey);

        if ($this->translations !== null) {
            return;
        }

        $this->translations = $this->fileParser->parseAll($this->translationsPath);

        if (!empty($this->translations)) {
            $this->cacheManager->put($cacheKey, $this->translations);
        }
    }


    protected function parseKey(string $key): array
    {
        $parts = explode('.', $key, 2);

        if (count($parts) !== 2) {
            throw new TranslationKeyNotFoundException($key);
        }

        return $parts;
    }


    protected function findTranslation(string $file, string $key, string $locale): string
    {
        if (isset($this->translations[$file][$locale][$key])) {
            return $this->translations[$file][$locale][$key];
        }

        if ($locale !== $this->fallbackLocale) {
            if (isset($this->translations[$file][$this->fallbackLocale][$key])) {
                return $this->translations[$file][$this->fallbackLocale][$key];
            }
        }

        if (!isset($this->translations[$file][$locale]) &&
            !isset($this->translations[$file][$this->fallbackLocale])) {
            throw new LocaleNotFoundException($locale);
        }

        throw new TranslationKeyNotFoundException($key);
    }

    protected function makeReplacements(string $translation, array $replace): string
    {
        if (empty($replace)) {
            return $translation;
        }

        foreach ($replace as $key => $value) {
            $translation = str_replace(
                [':' . $key, ':' . strtoupper($key), ':' . ucfirst($key)],
                [$value, strtoupper($value), ucfirst($value)],
                $translation
            );
        }

        return $translation;
    }
}
