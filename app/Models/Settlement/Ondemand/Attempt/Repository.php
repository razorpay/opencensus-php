<?php

namespace RZP\Models\Settlement\Ondemand\Attempt;

use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand.attempt';

    public function findById($id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->first();
    }
}
