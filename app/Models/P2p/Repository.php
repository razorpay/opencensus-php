<?php

namespace RZP\Models\P2p;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p';

    public function fetchWithSourceSink($id, $columns = ['*'])
    {
        return $this->newQuery()
                    ->select($columns)
                    ->with(['source', 'sink'])
                    ->find($id);
    }

    public function fetchPendingCollectRequests($customerId)
    {
        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, '=', $customerId)
                    ->where(Entity::TYPE, '=', 'collect')
                    ->where(Entity::STATUS, '=', 'created')
                    ->get();
    }
}
