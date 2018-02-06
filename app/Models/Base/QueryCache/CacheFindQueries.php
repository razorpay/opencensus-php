<?php

namespace RZP\Models\Base\QueryCache;

trait CacheFindQueries
{
    public function find($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->remember(static::CACHE_TTL)
                    ->cacheTags($this->entity . '_'. $id)
                    ->find($id, $columns);
    }
}
