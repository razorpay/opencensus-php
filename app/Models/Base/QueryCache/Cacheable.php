<?php

namespace RZP\Models\Base\QueryCache;

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
     * @return CacheQueryBuilder;
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        $grammar = $conn->getQueryGrammar();

        $builder = new CacheQueryBuilder($conn, $grammar, $conn->getPostProcessor());

        $driver = $this->getQueryCacheDriver();

        $builder->cacheDriver($driver);

        $builder->prefix($this->getCachePrefix());

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

    /**
     * Gets the prefix to be used for the query cache key.
     * It is of the form "rememberable:v1:<entity>"
     * The version prefix is for deleting all cached values
     * for an entity corresponding to the version number. If the entity
     * attributes change, we need this to delete cached items corresponding
     * to this version.
     * The entity prefix is need to delete cached items corresponding to a
     * particular entity, if we want to bulk remove all keys corresponding
     * to a given entity.
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        $prefixArray = [
            Constants::QUERY_CACHE_PREFIX,
            static::QUERY_CACHE_VERSION,
            $this->entity,
        ];

        return implode(':', $prefixArray);
    }
}
