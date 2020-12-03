<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand.transfer';

    public function findById($id)
    {
        return $this->newQuery()
                    ->where(Entity::ID, $id)
                    ->first();
    }
}
