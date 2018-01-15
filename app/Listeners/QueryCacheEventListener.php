<?php

namespace RZP\Listeners;

use App;
use Cache;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

use RZP\Trace\TraceCode;
use RZP\Models\Base\QueryCache\Constants;

class QueryCacheEventListener
{
    protected $event;

    public function handle($event)
    {
        $this->event = $event;

        $trace = App::getFacadeRoot()['trace'];

        $isQueryCacheEvent = strpos($event->key, Constants::QUERY_CACHE_PREFIX);

        //
        // Only handle rememberable events
        //
        if ($isQueryCacheEvent === false)
        {
            return;
        }

        try
        {
            $this->incrementCacheCounter();
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QUERY_CACHE_EVENT_ERROR
            );
        }
    }

    protected function incrementCacheCounter()
    {
        $prefix = $this->getCounterKeyPrefix();

        $suffix = $this->getCounterKeySuffix();

        $counterKey = $prefix . '_' . $suffix;

        Cache::increment($counterKey);
    }

    /**
     * Gets the entity name from the tag to be used
     * as counter key preifx.
     * TODO: This logic is simplistic and won't work
     * for entity names which have underscore. Need to fix
     *
     * @return string
     */
    protected function getCounterKeyPrefix(): string
    {
        $tags = $this->event->tags;

        $entityTag = array_first($this->event->tags, function ($tag)
        {
            return (str_contains($tag, '_') === true);
        });

        return substr($entityTag, 0, strpos($entityTag, '_'));
    }

    protected function getCounterKeySuffix(): string
    {
        switch (true)
        {
            case $this->event instanceof CacheMissed:
                return Constants::CACHE_MISSES;

            case $this->event instanceof CacheHit:
                return Constants::CACHE_HITS;

            case $this->event instanceof KeyWritten:
                return Constants::CACHE_WRITES;

            case $this->event instanceof KeyForgotten:
                return Constants::CACHE_FLUSHES;
        }
    }
}
