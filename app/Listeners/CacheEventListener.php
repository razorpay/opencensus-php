<?php

namespace RZP\Listeners;

use Cache;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Cache\Events;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Base\QueryCache\Constants;

class CacheEventListener
{
    const DEFAULT_DIMENSIONS = [
        'version' => 'none',
        'entity'  => 'none',
    ];

    /**
     * @var string
     */
    protected $event;

    /**
     * @var Trace
     */
    protected $trace;

    public function handle($event)
    {
        $this->event = $event;

        $this->trace = app('trace');

        $cacheEventType = $this->getCacheEventType();

        if (isset($cacheEventType) === false)
        {
            return;
        }

        try
        {
            $this->pushMetrics($cacheEventType);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::METRIC_CACHE_EVENT_ERROR
            );
        }
    }

    protected function pushMetrics($cacheEventType)
    {
        $dimensions = $this->getDimensions($cacheEventType);

        $dimensions[Metric::LABEL_TYPE] = $cacheEventType;

        $this->trace->count($this->getMetricName(), $dimensions);
    }

    protected function getCacheEventType()
    {
        switch (true)
        {
            case str_contains($this->event->key, Constants::QUERY_CACHE_PREFIX):
                return Metric::TYPE_QUERY_CACHE;

            case str_contains($this->event->key, Constants::UPI_POLLING_CACHE_PREFIX):
                return Metric::TYPE_UPI_POLLING;

            default:
                return null;
        }
    }

    protected function getDimensions($cacheEventType)
    {
        switch ($cacheEventType)
        {
            case Metric::TYPE_QUERY_CACHE:
                return $this->getQueryCacheDimensions();

            case Metric::TYPE_UPI_POLLING:
            default:
                return self::DEFAULT_DIMENSIONS;
                break;
        }
    }

    protected function getMetricName(): string
    {
        switch (true)
        {
            case $this->event instanceof Events\CacheMissed:
                return Metric::CACHE_MISSES_TOTAL;

            case $this->event instanceof Events\CacheHit:
                return Metric::CACHE_HITS_TOTAL;

            case $this->event instanceof Events\KeyWritten:
                return Metric::CACHE_WRITES_TOTAL;

            case $this->event instanceof Events\KeyForgotten:
                return Metric::CACHE_FLUSHES_TOTAL;
        }
    }

    protected function getQueryCacheDimensions()
    {
        if (preg_match('/^rememberable:(?<version>[^:]*):(?<entity>[^:]*).*$/', $this->event->key, $matches) === 1)
        {
            return array_only($matches, ['version', 'entity']);
        }
        else
        {
            return self::DEFAULT_DIMENSIONS;
        }
    }
}
