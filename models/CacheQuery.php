<?php

namespace Rhymix\Modules\Querynext\Models;

class CacheQuery
{
    private static $cache_prefix = 'cache_v1_';
    private $module_name;
    private $query_name;

    private function __construct(string $query)
    {
        [$this->module_name, $this->query_name] = explode('.', $query);
    }

    public static function getInstance(string $query): self
    {
        return new self($query);
    }

    public function isCached(): bool
    {
        // check file exists
        return file_exists($this->getCacheFilePath());
    }

    public function saveCache(string $data): void
    {
        if (!is_writable($this->getCacheSavePath())) {
            throw new \Exception('Cache save path is not writable');
        }

        $file_path = $this->getCacheFilePath();

        if (file_exists($file_path)) {
            unlink($file_path);
        }

        file_put_contents($file_path, $data);
    }

    public function getCacheQueryName(): string
    {
        return self::$cache_prefix . $this->module_name . '_' . strtolower($this->query_name);
    }

    protected function getCacheFileName(): string
    {
        return $this->getCacheQueryName() . '.xml';
    }

    protected function getCacheSavePath(): string
    {
        return 'modules/querynext/queries/';
    }

    protected function getCacheFilePath(): string
    {
        return $this->getCacheSavePath() . $this->getCacheFileName();
    }
}