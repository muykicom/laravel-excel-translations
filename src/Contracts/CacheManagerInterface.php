<?php

namespace Muyki\LaravelExcelTranslations\Contracts;

interface CacheManagerInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key) : mixed;

    /**
     * @param string $key
     * @param $data
     * @param int $ttl
     * @return bool
     */
    public function put(string $key, $data, int $ttl) : bool;

    /**
     * @param string $key
     * @return bool
     */
    public function forget(string $key) : bool;

    /**
     * @return bool
     */
    public function flush() : bool;
}
