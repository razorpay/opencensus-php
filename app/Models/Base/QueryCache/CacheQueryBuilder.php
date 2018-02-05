<?php

namespace RZP\Models\Base\QueryCache;

use App;
use Config;
use Illuminate\Support\Collection;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Watson\Rememberable\Query\Builder as RememberableQueryBuilder;

use RZP\Trace\TraceCode;

/**
 * Overrides rememberable package's Builder class, as we need to add
 * exception handling, in case Redis throws an error
 */
class CacheQueryBuilder extends RememberableQueryBuilder
{
    /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array $columns
     *
     * @return array|Collection
     */
    public function getCached($columns = ['*'])
    {
        $trace = App::getFacadeRoot()['trace'];

        $mock = Config::get('app.query_cache.mock');

        //
        // If query cache is mocked, we directly hit the db
        //
        if ($mock === true)
        {
            return IlluminateQueryBuilder::get($columns);
        }

        try
        {
            return parent::getCached($columns);
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QUERY_CACHE_STORE_ERROR,
                $columns);

            return IlluminateQueryBuilder::get($columns);
        }
    }

    /**
     * Flush the cache for the current model or a given tag name
     *
     * This is overridden, here as the parent implementation does
     * not have exception handling and also does not support using
     * specific connection for flushing.
     *
     * @param  mixed  $cacheTags
     * @return boolean
     */
    public function flushCache($cacheTags = null)
    {
        $mock = Config::get('app.query_cache.mock');

        //
        // If query cache is mocked we do not need to make cache flush call
        //
        if ($mock === true)
        {
            return true;
        }

        $trace = App::getFacadeRoot()['trace'];

        $this->cacheTags($cacheTags);

        $cache = $this->getCache();

        try
        {
            $cache->flush();

            //
            // Firing a KeyForgotten event here, to increment the cache_flushes
            // counter. This is to detect, how many flushes happened due to entity update
            //
            event(new KeyForgotten(Constants::QUERY_CACHE_PREFIX, [
                $cacheTags
            ]));
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QUERY_CACHE_FLUSH_ERROR,
                [
                    'tags' => $cacheTags
                ]);
        }

        return true;
    }
}

