<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_access_map';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::ENTITY_ID   => 'sometimes|string|size:14'
    ];

    public function getMerchantAccessEntityMapping(
        string $merchantId,
        string $entityId,
        string $entityType)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->first();
    }
}
