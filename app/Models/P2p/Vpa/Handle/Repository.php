<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_handle';

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function findByAcquirer(string $acquirer , bool $active)
    {
        $query = $this->newQuery()
                      ->where(Entity::ACQUIRER, $acquirer)->where(Entity::ACTIVE, $active);

        return $query->first();
    }
}
