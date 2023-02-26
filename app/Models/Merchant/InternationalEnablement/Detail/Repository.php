<?php

namespace RZP\Models\Merchant\InternationalEnablement\Detail;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'international_enablement_detail';

    public function getLatest(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }
    
    public function getProductsFromEntityId(string $internationalEnablementId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::PRODUCTS)
            ->where(Entity::ID, $internationalEnablementId)
            ->get()
            ->pluck(Entity::PRODUCTS)
            ->first();
    }
}
