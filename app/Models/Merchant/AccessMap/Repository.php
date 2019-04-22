<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_access_map';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE     => 'sometimes|string|max:255',
        Entity::ENTITY_ID       => 'sometimes|string|size:14',
        Entity::ENTITY_OWNER_ID => 'sometimes|string|size:14',
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
     * Returns the access map that links the submerchantId with a non pure-platform partner.
     *
     * @param string $subMerchantId
     *
     * @return Entity|null
     */
    public function getNonPurePlatformPartnerMapping(string $subMerchantId)
    {
        $accessMapsEntityOwnerId = $this->dbColumn(Entity::ENTITY_OWNER_ID);
        $merchantsId             = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantsPartnerType    = Table::MERCHANT . '.' . Merchant\Entity::PARTNER_TYPE;

        return $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->merchantId($subMerchantId)
                    ->join(Table::MERCHANT, $accessMapsEntityOwnerId, $merchantsId)
                    ->where($merchantsPartnerType, '!=', Merchant\Constants::PURE_PLATFORM)
                    ->first();
    }

    public function fetchAffiliatedPartnersForSubmerchant(string $subMerchantId)
    {
        $accessMapsEntityOwnerId = $this->dbColumn(Entity::ENTITY_OWNER_ID);
        $merchantsId             = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        return $this->newQuery()
                    ->merchantId($subMerchantId)
                    ->join(Table::MERCHANT, $accessMapsEntityOwnerId, $merchantsId)
                    ->with('entityOwner')
                    ->get();
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
    public function deleteMerchantAccessMapsByEntityIds(array $ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->delete();
    }
}
