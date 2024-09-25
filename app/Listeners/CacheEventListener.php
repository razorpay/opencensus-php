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

        try {
            //Add route for pricing entities metrics and log
            if($dimensions['entity'] == 'pricing') {
                $dimensions['route'] = app('request.ctx')->getRoute() ?? app('worker.ctx')->getJobName();
                $sampleRate = app('config')->get('cache.pricing_log_sample_rate') ?? 25;
                if (rand(1, 2500) <= $sampleRate) {
                    $this->logData($dimensions);
                }
            }
        } catch(\Throwable $e) {
        }

        $this->trace->count($this->getMetricName(), $dimensions);

    }

    protected function getCacheEventType()
    {
        switch (true)
        {
            case str_contains($this->event->key, Constants::ASV_CACHE_PREFIX):
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
        else if (preg_match('/^tag:asv:{(?<entity>[^_]*).*$/', $this->event->key, $matches) === 1)
        {
            return ['version' =>'v2', 'entity' => $matches['entity'] ?? 'none'];
        }
        else
        {
            return self::DEFAULT_DIMENSIONS;
        }
    }

    public function getLogData()
    {
        $runningInQueue = app()->runningInQueue();
        $logData        = ['route' => 'none', 'async_job_name' => 'none'];
        if ($runningInQueue === true) {
            $logData['async_job_name'] = app('worker.ctx')->getJobName();
            $logData['mode']           = app('worker.ctx')->getMode();
        } else {
            $logData['route']             = app('request.ctx')->getRoute();
            $logData['internal_app_name'] = app('request.ctx')->getInternalAppName();
            $logData['mode']              = app('request.ctx')->getMode();
        }
        $logData['is_transaction_active'] = app('repo')->isTransactionActive();
        $logData['trace'] = $this->getTrace();

        return $logData;
    }

    protected function getTrace()
    {
        $backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30);
        foreach ($backTrace as $index => $trace) {
            if (isset($trace['class']) && $trace['class'] === 'RZP\Models\Pricing\Repository') {
                return array_slice($backTrace, $index, 5);
            }
        }
        return $backTrace;
    }

    protected function logData($metricDimensions)
    {
        $logData = array_merge($metricDimensions, $this->getLogData());
        $this->trace->info(TraceCode::PRICING_CACHE_ACCESS, $logData);
    }
}
