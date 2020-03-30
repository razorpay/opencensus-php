<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'upi_metadata';

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc')
              ->orderBy(Entity::PAYMENT_ID, 'desc');
    }
}
