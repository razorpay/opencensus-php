<?php

namespace RZP\Listeners;

use App;
use Cache;
use RZP\Trace\TraceCode;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;

class QueryCacheEventListener
{
    const QUERY_CACHE_KEY = 'rememberable';

    const CACHE_HITS      = 'cache_hits';
    const CACHE_MISSES    = 'cache_misses';
    const CACHE_WRITES    = 'cache_writes';
    const CACHE_FLUSHES   = 'cache_flushes';

    protected $event;

    public function handle($event)
    {
        $this->event = $event;

        $trace = App::getFacadeRoot()['trace'];

        $isQueryCacheEvent = strpos($event->key, self::QUERY_CACHE_KEY);

        //
        // Only trace rememberable events
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

        s($counterKey);

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

        foreach ($tags as $tag)
        {
            if (str_contains($tag, '_') === true)
            {
                return substr($tag, 0, strpos($tag, '_'));
            }
        }
    }

    protected function getCounterKeySuffix(): string
    {
        switch (true)
        {
            case $this->event instanceof CacheMissed:
                return self::CACHE_MISSES;

            case $this->event instanceof CacheHit:
                return self::CACHE_HITS;

            case $this->event instanceof KeyWritten:
                return self::CACHE_WRITES;

            case $this->event instanceof KeyForgotten:
                return self::CACHE_FLUSHES;
        }
    }
}
