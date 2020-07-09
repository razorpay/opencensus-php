<?php

namespace RZP\Models\Settlement\Ondemand;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand';

    public function findByIdAndMerchantId($settlementOndemandId, $merchantId)
    {
        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->first();
    }
}
