<?php

namespace RZP\Base;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Database\Query\Builder as IlluminateQueryBuilder;
use Watson\Rememberable\Query\Builder as RememberableQueryBuilder;

use RZP\Constants\Mode;
use RZP\Trace\TraceCode;

/**
 * Overriden rememberable package's Builder class, as we need to add
 * exception handling, in case redis throws an error
 */
class QueryBuilder extends RememberableQueryBuilder
{
     /**
     * Execute the query as a cached "select" statement.
     *
     * @param  array  $columns
     * @return array
     */
    public function getCached($columns = ['*'])
    {
        $trace = App::getFacadeRoot()['trace'];

        $connection = $this->getQueryCacheConnection();

        $this->cacheDriver($connection);

        try
        {
            return parent::getCached($columns);
        }
        catch (\Throwable $e)
        {
            $trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::QUERY_CACHE_ERROR,
                $columns);

            return IlluminateQueryBuilder::get($columns);
        }
    }

    /**
     * Flush the cache for the current model or a given tag name
     * This is overridden, here as the parent implementation does
     * not support setting specific connection to the store.
     *
     * @param  mixed  $cacheTags
     * @return boolean
     */
    public function flushCache($cacheTags = null)
    {
        $store = app('cache')->getStore();

        $connection = $this->getQueryCacheConnection();

        $store->setConnection($connection);

        s($connection, $cacheTags);

        if (method_exists($store, 'tags') === false)
        {
            return false;
        }

        $cacheTags = $cacheTags ?: $this->cacheTags;

        $store->tags($cacheTags)->flush();

        return true;
    }

    /**
     * Gets the query cache connection to use depending on the mode set.
     * If mode is null, the test mode connection is used.
     *
     * @return string
     */
    protected function getQueryCacheConnection(): string
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? null;

        return ($mode === Mode::LIVE) ? 'query_cache_live' : 'query_cache_test';
    }
}

