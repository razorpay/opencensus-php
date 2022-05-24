<?php

namespace RZP\Models\PaymentLink;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Jobs\PaymentPageProcessor;
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

    /**
     * @var \Razorpay\Trace\Logger
     */
    private $trace;

    public function __construct($driver = null)
    {
        $this->app = App::getFacadeRoot();

        $this->trace = $this->app['trace'];

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

        $fromCache = true;

        $expanded = $this
            ->cache
            ->remember($this->getSlugMapCacheKey($hash), $this->getSlugMapCacheTTL(), function () use ($hash, &$fromCache) {
                $fromCache = false;

                return $this->elfin->expand($hash);
            });

        $metrics = $fromCache === true
            ? Metric::PAYMENT_PAGE_GIMLI_CACHE_HIT_COUNT
            : Metric::PAYMENT_PAGE_GIMLI_CACHE_MISS_COUNT;

        $this->trace->count($metrics);

        return $expanded;
    }

    /**
     * @param string      $hash
     * @param string|null $domain
     *
     * @return array|mixed|void
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    public function expandAndGetMetadata(string $hash, ?string $domain = null)
    {
        $details = $this->getFromCustomUrl($hash, $domain);

        $context = [
            'hash'      => $hash,
            'domain'    => $domain,
        ];

        if ($details !== null)
        {
            $this->trace->count(Metric::NOCODE_CUSTOM_URL_CONSIDERED_COUNT);

            $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_CONSIDERED, $context);

            return $details->trashed() ? null : $details->getMetaData();
        }

        $details = $this->expand($hash);

        if ($details !== null)
        {
            $this->trace->count(Metric::NOCODE_CUSTOM_URL_NOT_CONSIDERED_COUNT);

            $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_NOT_CONSIDERED, $context);

            $this->dispatchCustomUrlUpsert($details);

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

    /**
     * @param string      $slug
     * @param string|null $domain
     *
     * @return \RZP\Models\PaymentLink\NocodeCustomUrl\Entity|null
     * @throws \RZP\Exception\BadRequestValidationFailureException
     */
    private function getFromCustomUrl(string $slug, ?string $domain): ?NocodeCustomUrl\Entity
    {
        if (empty($domain) === true)
        {
            return null;
        }

        try {
            return (new NocodeCustomUrl\Core())->getForHosted($slug, $domain);
        }
        catch (\Throwable $e)
        {
            $this->trace->count(Metric::NOCODE_CUSTOM_URL_CALLS_FAILED_COUNT);

            $this->trace->error(TraceCode::NOCODE_CUSTOM_URL_UPSERT_FAILED, [
                'slug'      => $slug,
                'domain'    => $domain,
            ]);

            $this->trace->traceException($e);
        }
    }

    /**
     * @param array $gimliResponse
     *
     * @return void
     */
    private function dispatchCustomUrlUpsert(array $gimliResponse): void
    {
        if (empty($gimliResponse) === true)
        {
            return;
        }

        $mode = array_get($gimliResponse, 'url_aliases.0.metadata.mode');

        if(empty($mode) === true)
        {
            $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_UPSERT_FAILED, [
                'Mode not found',
                $gimliResponse
            ]);

            return;
        }

        $this->app->instance('rzp.mode', $mode);

        $this->trace->info(TraceCode::NOCODE_CUSTOM_URL_UPSERT_QUEUED, $gimliResponse);

        PaymentPageProcessor::dispatch($mode, [
            'event'             => PaymentPageProcessor::NOCODE_CUSTOM_URL_UPSERT_FROM_HOSTED_FLOW,
            'gimli_response'    => $gimliResponse,
        ]);
    }
}
