<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_access_map';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:255',
        Entity::ENTITY_ID   => 'sometimes|string|size:14'
    ];

    public function findMerchantAccessMapOnEntityId(
        string $merchantId,
        string $entityId,
        string $entityType)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->first();
    }

    public function fetchMerchantAccessMapsOnEntity(
        string $merchantId,
        string $entityType): Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
    }
}
