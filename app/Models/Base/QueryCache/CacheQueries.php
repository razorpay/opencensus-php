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
    public function find($id, $columns = ['*'], string $connectionType = null)
    {
        $cacheTtl = $this->getCacheTtl();

        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->remember($cacheTtl)
                    ->cacheTags($this->entity . '_'. $id)
                    ->find($id, $columns);
    }

    public function getCacheTtl(): int
    {
        // Multiplying by 60 since cache put() expect ttl in seconds
        return (E::CACHED_ENTITIES[$this->entity][Constants::TTL] ?? Constants::DEFAULT_QUERY_CACHE_TTL_MINS) * 60;
    }
}
