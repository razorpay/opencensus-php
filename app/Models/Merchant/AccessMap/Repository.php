<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use \RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZp\Models\Merchant\MerchantApplications as MerchantApp;

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

    public function getByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->first();
    }

    public function fetchSubMerchants(array $merchantIdList)
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->distinct()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchSubMerchantReferredByPartner(string $submerchantId, string $partnerId)
    {
        $accessMapsEntityId   = $this->dbColumn(Entity::ENTITY_ID);
        $accessMapsEntityType = Table::MERCHANT_ACCESS_MAP . '.' . Entity::ENTITY_TYPE;
        $applicationIds       = $this->repo->merchant_application->dbColumn(MerchantApp\Entity::APPLICATION_ID);
        $applicationType      = Table::MERCHANT_APPLICATION . '.' . MerchantApp\Entity::TYPE;
        $applicationDeleted   = Table::MERCHANT_APPLICATION . '.' . MerchantApp\Entity::DELETED_AT;

        return $this->newQuery()
                    ->merchantId($submerchantId)
                    ->join(Table::MERCHANT_APPLICATION, $accessMapsEntityId, $applicationIds)
                    ->where($accessMapsEntityType, '=', Entity::APPLICATION)
                    ->where($applicationType, '=', 'referred')
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->whereNull($applicationDeleted)
                    ->first();
    }

    public function findMerchantAccessMapOnEntityIds(string $merchantId, array $entityIds, string $entityType): Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->whereIn(Entity::ENTITY_ID, $entityIds)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
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
     * Returns access maps linking the submerchant and the partner
     *
     * @param string $subMerchantId
     * @param string $partnerId
     *
     * @return Base\PublicCollection
     */
    public function fetchAccessMapForMerchantIdAndOwnerId(string $subMerchantId, string $partnerId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $subMerchantId)
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->get();
    }

    /**
     * @param array $merchantIds
     *
     * @return array
     */
    public function fetchMerchantsMappedToPartner(array $merchantIds): array
    {
        $subMerchantIds = [];

        if (empty($merchantIds) === true)
        {
            return $subMerchantIds;
        }

        $chunkedIdsList = array_chunk($merchantIds, 5000);

        foreach ($chunkedIdsList as $chunkedIds)
        {
            $accessMaps = $this->newQuery()
                               ->select(Entity::MERCHANT_ID)
                               ->whereIn(Entity::MERCHANT_ID, $chunkedIds)
                               ->get();

            foreach ($accessMaps as $accessMap)
            {
                $subMerchantIds[] = $accessMap->getAttribute(Entity::MERCHANT_ID);
            }
        }

        return $subMerchantIds;
    }

    /**
     * Returns the access maps that links a submerchant to the given app type of a partner.
     *
     * @param string $subMerchantId
     * @param string $appType
     *
     * @return Entity|null
     */
    public function getMappingByApplicationType(string $subMerchantId, string $appType)
    {
        $accessMapsEntityId   = $this->dbColumn(Entity::ENTITY_ID);
        $accessMapsEntityType = Table::MERCHANT_ACCESS_MAP . '.' . Entity::ENTITY_TYPE;
        $applicationIds       = $this->repo->merchant_application->dbColumn(MerchantApp\Entity::APPLICATION_ID);
        $applicationType      = Table::MERCHANT_APPLICATION . '.' . MerchantApp\Entity::TYPE;
        $applicationDeleted   = Table::MERCHANT_APPLICATION . '.' . MerchantApp\Entity::DELETED_AT;

        return $this->newQuery()
                    ->select($this->getTableName() . '.*')
                    ->merchantId($subMerchantId)
                    ->join(Table::MERCHANT_APPLICATION, $accessMapsEntityId, $applicationIds)
                    ->where($accessMapsEntityType, '=', Entity::APPLICATION)
                    ->where($applicationType, '=', $appType)
                    ->whereNull($applicationDeleted)
                    ->get();
    }

    public function getAllMappingsByApplicationType(string $appType, string $afterId, int $chunk)
    {
        $accessMapEntityId = $this->repo->merchant_access_map->dbColumn("entity_id");
        $accessMapId = $this->repo->merchant_access_map->dbColumn(Entity::ID);
        $merchantApplicationId = $this->repo->merchant_application->dbColumn(Entity::APPLICATION_ID);

        return $this->newQuery()
            ->select($accessMapId)
            ->where($accessMapId, '>', $afterId)
            ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
            ->where(MerchantApplications\Entity::TYPE, $appType)
            ->join(Table::MERCHANT_APPLICATION, $accessMapEntityId, '=', $merchantApplicationId)
            ->orderBy($accessMapId)
            ->take($chunk)
            ->get();
    }

    public function getSubMerchantCount(string $partnerId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->distinct()
                    ->count();
    }

    public function isSubmerchantPresentForPartner(string $partnerId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->exists();
    }

    public function isLiveSubmerchantPresentForPartner(string $partnerId)
    {
        $accessMapsMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantsId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantsLive = Table::MERCHANT . '.' . Merchant\Entity::LIVE;

        return $this->newQuery()
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->join(Table::MERCHANT, $accessMapsMerchantId, $merchantsId)
                    ->where($merchantsLive, true)
                    ->exists();
    }

    public function getAllMappingsByEntityIdAndEntityOwnerId(string $entityId, string $entityOwnerId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_OWNER_ID, $entityOwnerId)
                    ->get();
    }

    public function getMerchantIdForSubmerchantsOfAPartner(string $partnerId)
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->get();
    }
}
