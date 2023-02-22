<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Constants\Product;
use RZP\Base\ConnectionType;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Merchant\MerchantApplications;
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

    public function fetchEntityOwnerIdsForSubmerchant(string $submerchantId)
    {
        return $this->newQuery()
                    ->select(Entity::ENTITY_OWNER_ID)
                    ->where(Entity::MERCHANT_ID, $submerchantId)
                    ->get()
                    ->pluck(Entity::ENTITY_OWNER_ID);
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
        $accessMapsCreatedAt  = Table::MERCHANT_ACCESS_MAP . '.' . Entity::CREATED_AT;
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
                    ->orderBy($accessMapsCreatedAt, 'asc')
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

public function getAllMappingsByApplicationTypeWithTrashed(string $appType, string $afterId, int $chunk)
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
            ->withTrashed()
            ->get();
    }

    public function findManyWithTrashed(array $ids)
    {
        return $this->newQuery()
            ->select('*')
            ->whereIn(Entity::ID, $ids)
            ->withTrashed()
            ->get();
    }

    public function findWithTrashed(string $id)
    {
        return $this->newQuery()
            ->select('*')
            ->where(Entity::ID, $id)
            ->withTrashed()
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

    public function getAllMappingsByEntityIdAndEntityOwnerId(string $entityId, string $entityOwnerId, string $mode = null)
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->where(Entity::ENTITY_ID, $entityId)
                     ->where(Entity::ENTITY_OWNER_ID, $entityOwnerId)
                     ->orderBy(Entity::ID)
                     ->get();
    }

    /**
     * Fetch merchant access maps in sync for given entityId and entityOwnerId.
     * It fails if data is not in sync in test and live DB.
     *
     * @param   string  $entityId       the entity or application ID
     * @param   string  $entityOwnerId  the partner's merchant ID
     * @return  Base\PublicCollection
     * @throws  LogicException
     */
    public function fetchAccessMapsInSyncOrFail(string $entityId, string $entityOwnerId) : Base\PublicCollection
    {
        $liveEntities = $this->getAllMappingsByEntityIdAndEntityOwnerId($entityId, $entityOwnerId, 'live');
        $testEntities = $this->getAllMappingsByEntityIdAndEntityOwnerId($entityId, $entityOwnerId, 'test');

        $isSynced = $this->areEntitiesSyncOnLiveAndTest($liveEntities, $testEntities);
        if ($isSynced === true)
        {
            return $liveEntities;
        }
        else
        {
            $this->trace->critical(
                TraceCode::DATA_MISMATCH_ON_LIVE_AND_TEST,
                [
                    'on_live' => $liveEntities,
                    'on_test' => $testEntities
                ]
            );
            throw new LogicException("Data is not synced on Live and Test DB");
        }
    }

    public function getMerchantIdForSubmerchantsOfAPartner(string $partnerId)
    {
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->get();
    }

    public function getMappingsFromEntityOwnerId(string $entityOwnerId, $limit = null)
    {
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                      ->where(Entity::ENTITY_OWNER_ID, $entityOwnerId);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        return $query->get();
    }

    public function getSubMerchantsFromEntityOwnerId(string $entityOwnerId, $limit = null, $lastProcessedId = null)
    {
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::ENTITY_OWNER_ID, $entityOwnerId)
                    ->orderBy(Entity::ID);

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        if (empty($lastProcessedId) === false)
        {
            $query->where(Entity::ID, '>', $lastProcessedId);
        }

        return $query->get();
    }

    /**
     * Returns all the  submerchants who had done their first payment after the timestamp.
     *
     * @param string|null $afterId
     * @param int $chunk
     * @param int $from
     *
     * @return array
     */
    public function getTransactedSubmerchants(string $afterId = null, int $chunk, int $from) : array
    {
        $submerchantId       = $this->repo->merchant_access_map->dbColumn(Entity::MERCHANT_ID);
        $entityOwnerId       = $this->repo->merchant_access_map->dbColumn(Entity::ENTITY_OWNER_ID);
        $paymentMerchantId   = $this->repo->payment->dbColumn(Entity::MERCHANT_ID);
        $paymentCreatedAt    = $this->repo->payment->dbColumn(Entity::CREATED_AT);
        $accessMapCreatedAt  = $this->repo->merchant_access_map->dbColumn(Entity::CREATED_AT);
        $paymentAuthorizedAt = $this->repo->payment->dbColumn(Payment\Entity::AUTHORIZED_AT);

        $query =   $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                        ->select($submerchantId)
                        ->join(Table::PAYMENT, $submerchantId, '=', $paymentMerchantId)
                        ->where($paymentCreatedAt, '>=', $accessMapCreatedAt)
                        ->where($paymentCreatedAt, '>=', $from)
                        ->where($submerchantId, '!=', null)
                        ->where($entityOwnerId, '!=', null)
                        ->where($paymentAuthorizedAt, '!=', null)
                        ->groupBy($paymentMerchantId)
                        ->having(DB::raw('min(`merchant_access_map`.created_at)'), '>=', $from );// For getting the payments done after the from timestamp

        if (empty($afterId) === false)
        {
            $query->where($submerchantId, '>', $afterId);
        }

        if (empty($chunk) === false)
        {
            $query->take($chunk);
        }

        return $query->get()->pluck(Entity::MERCHANT_ID)->toArray();
    }

    public function filterSubmerchantIdsLinkedToAppIdsForProduct(
        array $applicationIds,
        array $submerchantIds,
        string $product = Product::PRIMARY,
        array $tags = []
    )
    {
        if (empty($applicationIds) === true)
        {
            return new Base\PublicCollection;
        }

        $accessMapsEntityId   = $this->dbColumn(Entity::ENTITY_ID);
        $accessMapsDeletedAt  = $this->dbColumn(Base\Entity::DELETED_AT);
        $accessMapsEntityType = $this->dbColumn(Entity::ENTITY_TYPE);
        $accessMapsMerchantId = $this->dbColumn(Base\PublicEntity::MERCHANT_ID);

        // filter merchant_access_map for application IDs
        $query = $this->newQuery()
                      ->select([$accessMapsMerchantId])
                      ->where($accessMapsEntityType, Entity::APPLICATION)
                      ->whereIn($accessMapsEntityId, $applicationIds)
                      ->whereNull($accessMapsDeletedAt);

        // filter merchant_access_map for submerchant IDs
        if (empty($submerchantIds) === false)
        {
            $query->whereIn($accessMapsMerchantId, $submerchantIds);
        }

        // join with merchant_users table to filter on product
        $merchantUsersRepo       = $this->repo->merchant_user;
        $merchantUsersMerchantId = $merchantUsersRepo->dbColumn(Merchant\MerchantUser\Entity::MERCHANT_ID);
        $merchantUsersProduct    = $merchantUsersRepo->dbColumn(Merchant\MerchantUser\Entity::PRODUCT);

        $query->join(Table::MERCHANT_USERS, $accessMapsMerchantId, '=', $merchantUsersMerchantId)
              ->where($merchantUsersProduct, $product)
              ->distinct();

        // join with tagging_tagged table to filter on tags
        $tags = array_unique(array_map('mb_strtolower', array_map('str_slug', $tags)));

        $tagsTable = 'tagging_tagged';

        $query->join($tagsTable, $tagsTable . '.taggable_id', $accessMapsMerchantId)
              ->where($tagsTable . '.taggable_type', '=', E::MERCHANT)
              ->whereIn($tagsTable . '.tag_slug', $tags)
              ->distinct();

        return $query->get();
    }
}
