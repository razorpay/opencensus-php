<?php

namespace RZP\Models\Payout\Batch;

use RZP\Constants;
use Database\Connection;
use RZP\Models\Base\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected $entity = Constants\Entity::PAYOUTS_BATCH;

    // This is added here to avoid 500 error when trying to fetch entities in the admin dashboard
    // The original method called orderBy('id'), which failed as our entity does not have an id column
    // Thus replaced the call to id column with batch_id column
    protected function addQueryOrder($query)
    {
        if (($query->getConnection()->getName() !== Connection::DATA_WAREHOUSE_LIVE) and
            ($query->getConnection()->getName() !== Connection::DATA_WAREHOUSE_TEST))
        {
            $query->orderBy($this->dbColumn(Entity::CREATED_AT), 'desc');
        }

        $query->orderBy($this->dbColumn(Entity::BATCH_ID), 'desc');
    }
}
