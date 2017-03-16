<?php

namespace RZP\Models\Ecollect;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'ecollect';

    public function findByTransactionId($transactionId)
    {
        return $this->newQuery()
                    ->where(Entity::TRANSACTION_ID, '=', $transactionId)
                    ->first();
    }
}
