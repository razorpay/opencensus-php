<?php

namespace RZP\Models\PaymentLink;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use RZP\Services\Elfin\Service as ElfinService;

final class ElfinWrapper
{
    /**
     * @var \RZP\Services\Elfin\Impl\Gimli
     */
    private $elfin;

    /**
     * @var mixed|object
     */
    private $app;

    /**
     * @var string
     */
    private $driver;

    /**
     * @var \Illuminate\Contracts\Cache\Repository
     */
    private $cache;

    public function __construct($driver = null)
    {
        $this->app = App::getFacadeRoot();

        $this->driver = $driver;

        $this->elfin = $this->app['elfin'];

        $this->cache = $this->app['cache'];

        if ($driver !== null)
        {
            $this->elfin = $this->elfin->driver($driver);
        }
    }

    /**
     * @param string $hash
     *
     * @return array|null
     */
    public function expand(string $hash): ?array
    {
        if ($this->driver !== ElfinService::GIMLI)
        {
            return null;
        }

        return $this
            ->cache
            ->remember($this->getSlugMapCacheKey($hash), $this->getSlugMapCacheTTL(), function () use ($hash) {
                return $this->elfin->expand($hash);
            });
    }

    /**
     * @param string $hash
     *
     * @return array|null
     */
    public function expandAndGetMetadata(string $hash)
    {
        $details = $this->expand($hash);

        if ($details !== null)
        {
            return $details['url_aliases'][0]['metadata'];
        }
    }

    /**
     * @return void
     */
    public function setNoFallback()
    {
        $this->elfin->setNoFallback();
    }

    /**
     * @param string $url
     * @param array  $input
     * @param bool   $fail
     *
     * @return string
     * @throws \Throwable
     */
    public function shorten(string $url, array $input = [], bool $fail = false, bool $shouldCache = true): string
    {
        $shortUrl = $this->elfin->shorten($url, $input, $fail);

        if ($shouldCache === true)
        {
            // this will cache the response
            $this->expand($this->getHashFromUrl($shortUrl));
        }

        return $shortUrl;
    }

    /**
     * @param string $hash
     * @param string $input
     */
    public function update(string $hash, string $input, bool $shouldCache = true)
    {
        $resultHash = $this->elfin->update($hash, $input);

        if ($shouldCache === true)
        {
            // this will cache the response
            $this->expand($hash);
        }

        return $resultHash;
    }

    /**
     * @param $slug
     *
     * @return string
     */
    public function getSlugMapCacheKey($slug): string
    {
        return $this->getBaseCacheKey() . ":" . $slug;
    }

    /**
     * @return string
     */
    public function getBaseCacheKey(): string
    {
        $prefix = $this->getCachePrefix();

        return $prefix . ":"
            . $this->app->env . ":"
            . Constants::SLUG_CACHE_KEY;
    }

    /**
     * @param string $hash
     *
     * @return void
     */
    public function clearCacheByHash(string $hash): void
    {
        $this->cache->forget($this->getSlugMapCacheKey($hash));
    }

    /**
     * @return string
     */
    private function getCachePrefix(): string
    {
        return Config::get("app.nocode.cache.prefix");
    }

    /**
     * @return int
     */
    private function getSlugMapCacheTTL(): int
    {
        return (int) Config::get("app.nocode.cache.slug_ttl");
    }

    /**
     * @param string $url
     *
     * @return string|null
     */
    private function getHashFromUrl(string $url): ?string
    {
        $parts = explode('/', $url);

        return end($parts) ?: null;
    }
}
