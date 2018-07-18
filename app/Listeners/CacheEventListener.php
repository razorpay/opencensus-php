<?php

namespace RZP\Listeners;

use App;
use Cache;
use Metrics;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Cache\Events;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Base\QueryCache\Constants;

class CacheEventListener
{
    protected $event;

    public function handle($event)
    {
        $this->event = $event;

        $trace = App::getFacadeRoot()['trace'];

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
            $trace->traceException(
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

        Metrics::count($this->getMetricName(), 1, $dimensions);
    }

    protected function getCacheEventType()
    {
        if (str_contains($this->event->key, Constants::QUERY_CACHE_PREFIX) === true)
        {
            return Metric::TYPE_QUERY_CACHE;
        }
    }

    protected function getDimensions($cacheEventType)
    {
        switch ($cacheEventType)
        {
            case Metric::TYPE_QUERY_CACHE:
                return $this->getQueryCacheDimensions();

            default :
                throw new Exception\LogicException(
                    'Unhandled cache metric event type.',
                    null,
                    ['cache event type' => $cacheEventType]);
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
        $dimensions = [];

        if (preg_match('/^rememberable:(?<version>.*):(?<entity>.*):.*$/', $this->event->key, $matches) === 1)
        {
            $dimensions = array_only($matches, ['version', 'entity']);
        }

        return $dimensions;
    }
}
