<?php

namespace RZP\Models\UpiMandate;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'upi_mandate';

    public function findByOrderId(string $orderId)
    {
        $upiMandate = $this->newQuery()
                           ->where(Entity::ORDER_ID, '=', $orderId)
                           ->first();

        return $upiMandate;
    }
}
