<?php

namespace RZP\Models\Base\Traits\QueryCache;

use App;
use RZP\Constants\Mode;
use RZP\Models\Base\QueryCache\CacheQueryBuilder;

/**
 * This traits overrides the newBaseQueryBuilder method, to
 * return an instance of the CacheQueryBuilder, which is required
 * for query caching. This trait needs to be included in whichever
 * entity we want to use query caching.
 */
trait Cacheable
{
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \RZP\Base\CacheQueryBuilder;
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $builder = new CacheQueryBuilder($conn, $grammar, $conn->getPostProcessor());

        $driver = $this->getQueryCacheDriver();

        $builder->cacheDriver($driver);

        return $builder;
    }

    /**
     * Gets the query cache driver to use depending on the mode set.
     * If mode is null, the test mode driver is used.
     *
     * @return string
     */
    protected function getQueryCacheDriver(): string
    {
        $app = App::getFacadeRoot();

        $mode = $app['rzp.mode'] ?? null;

        return ($mode === Mode::TEST) ? 'query_cache_test' : 'query_cache_live';
    }
}
