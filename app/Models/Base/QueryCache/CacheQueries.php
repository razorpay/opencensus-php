<?php

namespace RZP\Models\Base\QueryCache;

/**
 * This trait overrides the find query for entites which
 * want to cache the find query. The ttl for the cache entry
 * needs to be specified in the CACHE_TTL constant in the repository class
 */
trait CacheQueries
{
    public function find($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->remember(static::CACHE_TTL)
                    ->cacheTags($this->entity . '_'. $id)
                    ->find($id, $columns);
    }
}
