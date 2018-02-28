<?php

namespace RZP\Models\Base\QueryCache;

use RZP\Constants\Entity as E;

/**
 * This trait overrides the find query for entites which
 * want to cache the find query. The query cache ttl default
 * value is 5 minutes, but can be specifically set in the
 * CACHED_ENTITIES array.
 */
trait CacheQueries
{
    public function find($id, $columns = ['*'])
    {
        $cacheTtl = $this->getCacheTtl();

        return $this->newQuery()
                    ->remember($cacheTtl)
                    ->cacheTags($this->entity . '_'. $id)
                    ->find($id, $columns);
    }

    protected function getCacheTtl(): int
    {
        return E::CACHED_ENTITIES[$this->entity][Constants::TTL] ?? Constants::DEFAULT_QUERY_CACHE_TTL;
    }
}
