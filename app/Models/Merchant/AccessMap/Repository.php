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

    /**
     * @param string $merchantId
     * @param string $entityId
     * @param string $entityType
     *
     * @return mixed
     */
    public function findMerchantAccessMapOnEntityId(string $merchantId, string $entityId, string $entityType)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->first();
    }

    /**
     * @param string $merchantId
     * @param string $entityType
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantAccessMapsOnEntityType(string $merchantId, string $entityType): Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantAccessMapOnEntity(string $entityType, string $entityId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
    }

    /**
     * @param array $ids
     *
     * @return mixed
     */
    public function deleteMerchantAccessMapsByEntity(array $ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->delete();
    }
}
