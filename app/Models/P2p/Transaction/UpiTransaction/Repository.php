<?php

namespace RZP\Models\P2p\Transaction\UpiTransaction;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_upi_transaction';

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::TRANSACTION_ID, 'desc');
    }

    public function findAll(array $input)
    {
        $query =  $this->newQuery()->where($input);

        return $query->get();
    }
}
