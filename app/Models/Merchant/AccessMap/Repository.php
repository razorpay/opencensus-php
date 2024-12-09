<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Constants\Product;
use RZP\Base\ConnectionType;
use RZP\Constants\Entity as E;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Merchant\MerchantApplications;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\Traits\AsvEntityConnection;
use RZp\Models\Merchant\MerchantApplications as MerchantApp;
use RZP\Models\Merchant\Acs\AsvSdkIntegration;


class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;
    use AsvEntityConnection;

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
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setEntityId([$entityId]);
            $partnershipsRequest->setEntityType($entityType);
            $partnershipsRequest->setMerchantId([$merchantId]);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__,true);
            if(!$redirectToApi)
            {
                return $response;
            }
        }
        return $this->newQuery()
            ->merchantId($merchantId)
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->first();
    }

    public function getByMerchantId(string $merchantId, bool $fetchLatest = false)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setMerchantId([$merchantId]);
            $partnershipsRequest->setOrderBy([Entity::CREATED_AT]);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                if($fetchLatest)
                {
                    return empty($response) ? null : end($response);
                }
                return empty($response) ? null : $response[0];
            }
        }
        $query = $this->newQuery()
            ->merchantId($merchantId);
        if ($fetchLatest === true)
        {
            return $query->get()->last();
        }
        return $query->first();
    }

    public function fetchSubMerchants(array $merchantIdList)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setMerchantId($merchantIdList);
            $partnershipsRequest->setFields([Entity::MERCHANT_ID]);
            $partnershipsRequest->setDistinct(true);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                $collectionResponse=new PublicCollection($response);
                return $collectionResponse->pluck(Entity::MERCHANT_ID)->toArray();
            }
        }
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->distinct()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchSubMerchantReferredByPartner(string $submerchantId, string $partnerId)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setEntityId($entityIds);
            $partnershipsRequest->setEntityType($entityType);
            $partnershipsRequest->setMerchantId([$merchantId]);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                return new PublicCollection($response);
            }
        }

        return $this->newQuery()
            ->merchantId($merchantId)
            ->whereIn(Entity::ENTITY_ID, $entityIds)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->get();
    }

    /**
     * Returns the access map that links the submerchantId with a non pure-platform partner.
     * joins merchant application and fetches the first mapping without oauth application
     *
     * @param string $subMerchantId
     *
     * @return Entity|null
     */
    public function getNonPurePlatformPartnerMapping(string $subMerchantId)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        $newflow = (new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__);
        if ($newflow == true)
        {
            $accessMapsEntityId = $this->dbColumn(Entity::ENTITY_ID);
            $applicationId      = $this->repo->merchant_application->dbColumn(Merchant\MerchantApplications\Entity::APPLICATION_ID);
            $applicationType    = Table::MERCHANT_APPLICATION . '.' .Merchant\MerchantApplications\Entity::TYPE;

            return $this->newQueryWithConnection($this->getSlaveConnection())
                ->select($this->getTableName() . '.*')
                ->merchantId($subMerchantId)
                ->join(Table::MERCHANT_APPLICATION, $accessMapsEntityId, $applicationId)
                ->where($applicationType, '!=', Merchant\MerchantApplications\Entity::OAUTH)
                ->first();

        }
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        if ((new AsvRouter())->shouldRouteFilterToAsv(__FUNCTION__))
        {
            // fetch entity owner ids
            $accessMaps = $this->newQueryWithConnection($this->getSlaveConnection())
                ->where(Entity::MERCHANT_ID, $subMerchantId)
                ->get();

            $entityOwnerIds = array_values($accessMaps->pluck(Entity::ENTITY_OWNER_ID)->unique()->toArray());
            // fetch merchants for entity owner ids
            // check if transaction is active

            if ($this->isTransactionActive() === true)
            {
                $merchants = $this->repo->merchant->findMerchantsByIds($entityOwnerIds); // updated this to use asv db
            }
            else
            {
                $merchants = new Base\PublicCollection();
                foreach (array_chunk($entityOwnerIds, AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT) as $chunk) {
                    $merchants->push(...(new AsvSdkIntegration\Merchant())->fetchMerchantsByIds($chunk));
                }
                $this->resetConnectionOnModels($merchants, $this->getSlaveConnection());
            }

            $validMerchantAccessMaps = new Base\PublicCollection();
            foreach ($accessMaps as $accessMap)
            {
                $merchant = $merchants->where(Merchant\Entity::ID, $accessMap->getEntityOwnerId())->first();
                if(! empty($merchant))
                {
                    $accessMap->setRelation('entityOwner', $merchant);
                    $validMerchantAccessMaps->push($accessMap);
                }
            }
            return $validMerchantAccessMaps;
        }

        $accessMapsEntityOwnerId = $this->dbColumn(Entity::ENTITY_OWNER_ID);
        $merchantsId             = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        return $this->newQuery()
            ->merchantId($subMerchantId)
            ->join(Table::MERCHANT, $accessMapsEntityOwnerId, $merchantsId)
            ->with('entityOwner')
            ->get();
    }

    public function fetchEntityOwnerIdsForSubmerchant(string $submerchantId, bool $useSlave = false)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipRequest = new PartnershipsAccessMapDTO();
            $partnershipRequest->setMerchantId([$submerchantId]);
            $partnershipRequest->setFields([Entity::ENTITY_OWNER_ID]);
            [$redirectToApi, $response] = $this->fetchMerchantAccessMapsOnFilter($partnershipRequest,__FUNCTION__);
            if ($redirectToApi===false)
            {
                $responseCollection= new PublicCollection($response);
                return $responseCollection->pluck(Entity::ENTITY_OWNER_ID);
            }
        }

        $query = $this->newQuery();

        if ($useSlave)
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query->select(Entity::ENTITY_OWNER_ID)
            ->where(Entity::MERCHANT_ID, $submerchantId)
            ->get()
            ->pluck(Entity::ENTITY_OWNER_ID);
    }

    public function fetchEntityIdsForSubmerchant(string $submerchantId, bool $useSlave = false)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipRequest = new PartnershipsAccessMapDTO();
            $partnershipRequest->setMerchantId($submerchantId);
            $partnershipRequest->setFields([Entity::ENTITY_ID]);
            $partnershipRequest->setOrderBy([Entity::CREATED_AT]);
            [$redirectToApi, $response] = $this->fetchMerchantAccessMapsOnFilter($partnershipRequest);
            if ($redirectToApi===false)
            {
                $responseCollection= new PublicCollection($response);
                return $responseCollection->pluck(Entity::ENTITY_ID);
            }
        }

        $query = $this->newQuery();

        if ($useSlave)
        {
            $query = $this->newQueryWithConnection($this->getSlaveConnection());
        }

        return $query->select(Entity::ENTITY_ID)
            ->where(Entity::MERCHANT_ID, $submerchantId)
            ->orderBy(Entity::CREATED_AT, 'asc')
            ->get()
            ->pluck(Entity::ENTITY_ID);
    }

    /**
     * @param string $merchantId
     * @param string $entityType
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantAccessMapsOnEntityType(string $merchantId, string $entityType): Base\PublicCollection
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipRequest = new PartnershipsAccessMapDTO();
            $partnershipRequest->setMerchantId([$merchantId]);
            $partnershipRequest->setEntityType($entityType);
            [$redirectToApi, $response] = $this->fetchMerchantAccessMapsOnFilter($partnershipRequest,__FUNCTION__);
            if (!$redirectToApi)
            {
                return new PublicCollection($response);
            }
        }

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
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipRequest = new PartnershipsAccessMapDTO();
            $partnershipRequest->setEntityType($entityType);
            $partnershipRequest->setEntityId([$entityId]);
            [$redirectToApi, $response] = $this->fetchMerchantAccessMapsOnFilter($partnershipRequest,__FUNCTION__);
            if (!$redirectToApi)
            {
                return new PublicCollection($response);
            }
        }

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
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setMerchantId([$subMerchantId]);
            $partnershipsRequest->setEntityOwnerId($partnerId);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                return $response;
            }
        }
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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
    This will fetch all the submerchant Ids associated with only one partner.
    Ex -  if there are two submerchants S1 and S2
    S1 is associated with P1 partner
    S2 is associated with P1, P2 partner
    Then the below function for P1 ownerId will return Submerchant S1 only.
     */
    public function fetchSubMerchantIDsLinkedOnlyToAPartner(string $merchantId): array
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        $subMerchantIds = $this->newQuery()
            ->select(Entity::MERCHANT_ID)
            ->whereIn(Entity::MERCHANT_ID, function ($query) use ($merchantId) {
                $query->select(Entity::MERCHANT_ID)
                    ->from(Table::MERCHANT_ACCESS_MAP)
                    ->where(Entity::ENTITY_OWNER_ID, $merchantId)
                    ->groupBy(Entity::MERCHANT_ID)
                    ->pluck(Entity::MERCHANT_ID);
            })
            ->groupBy(Entity::MERCHANT_ID)
            ->havingRaw('COUNT(DISTINCT '.Entity::ENTITY_OWNER_ID.') = 1')
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();

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
        $experimentEnabled = $this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($experimentEnabled){
            [$redirectToApi, $response] = $this->fetchAccessMapsForSubmerchant($subMerchantId, $appType);
            if(!$redirectToApi){
                return $response;
            }
        }
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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

    /**
     * @param array $appIds
     * @param array $merchantIds
     *
     * @return array
     */
    public function filterSubMerchantsIdsMappedToAppId(array $appIds, array $merchantIds): array
    {
        if (empty($appIds) === true OR empty($merchantIds) === true)
        {
            return [];
        }
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setMerchantId($merchantIds);
            $partnershipsRequest->setEntityType(Entity::APPLICATION);
            $partnershipsRequest->setEntityId($appIds);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                $responseCollection=new PublicCollection($response);
                return $responseCollection->pluck(Entity::MERCHANT_ID)->toArray();
            }
        }

        $query = $this->newQuery()
                      ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                      ->whereIn(Entity::ENTITY_ID, $appIds)
                      ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                      ->whereNull(Entity::DELETED_AT);

        return $query->get()->pluck(Entity::MERCHANT_ID)->toArray();
    }

    /**
     * @param array $applicationIds
     *
     * @return array
     */
    public function fetchSubmerchantIdsFromAppIds(array $applicationIds): array
    {
        if (empty($applicationIds) === true)
        {
            return [];
        }

        return $this->getSubMerchantsIdsMappedToAppId($applicationIds);
    }

    /**
     * @param array $appIds
     *
     * @return array
     */
    public function getSubMerchantsIdsMappedToAppId(array $appIds): array
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setEntityType(Entity::APPLICATION);
            $partnershipsRequest->setEntityId($appIds);
            $partnershipsRequest->setFields([Entity::MERCHANT_ID]);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                $responseCollection=new PublicCollection($response);
                return $responseCollection->pluck(Entity::MERCHANT_ID)->toArray();
            }
        }
        $query = $this->newQuery()
                      ->where(Entity::ENTITY_TYPE, Entity::APPLICATION)
                      ->whereIn(Entity::ENTITY_ID, $appIds)
                      ->whereNull(Entity::DELETED_AT);

        return $query->get()->pluck(Entity::MERCHANT_ID)->toArray();

    }


    /**
     * Fetches all sub-merchants which are mapped to a list of partner application IDs
     *
     * @param array $applicationIds
     *
     * @return PublicCollection
     */
    public function fetchSubmerchantsFromAppIds(array $applicationIds): Base\PublicCollection
    {
       $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        if (empty($applicationIds) === true)
        {
            return new Base\PublicCollection;
        }

        $merchantIds = $this->getSubMerchantsIdsMappedToAppId($applicationIds);

        $merchants = new PublicCollection();
        foreach (array_chunk($merchantIds, AsvSdkIntegration\Base::FETCH_SERVICE_FILTER_LIMIT) as $chunk) {
            $merchants->push(...(new AsvSdkIntegration\Merchant())->fetchMerchantsByIds($chunk));
        }

        return $merchants->sortByDesc(
            [
                fn (Merchant\Entity $a, Merchant\Entity $b) => $a->getCreatedAt() <=> $b->getCreatedAt(),
                fn (Merchant\Entity $a, Merchant\Entity $b) => $a->getId() <=> $b->getId(),
            ]
        );
    }

    public function getSubMerchantCount(string $partnerId)
    {
        $experimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($experimentEnabled){
            [$redirectToApi,$response]=$this->fetchMerchantCount($partnerId);
            if(!$redirectToApi)
            {
                return $response;
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->distinct()
                    ->count();
    }

    public function isSubmerchantPresentForPartner(string $partnerId)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setEntityOwnerId($partnerId);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                return !empty($response);
            }
        }
        return $this->newQuery()
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->exists();
    }

    public function isLiveSubmerchantPresentForPartner(string $partnerId)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        $accessMapsMerchantId = $this->dbColumn(Entity::MERCHANT_ID);
        $merchantsId = $this->repo->merchant->dbColumn(Merchant\Entity::ID);
        $merchantsLive = Table::MERCHANT . '.' . Merchant\Entity::LIVE;

        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->join(Table::MERCHANT, $accessMapsMerchantId, $merchantsId)
                    ->where($merchantsLive, true)
                    ->exists();
    }

    public function fetchAllMappingsByEntityIdAndEntityOwnerId(array $entityIds, string $entityOwnerId, string $mode = null)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setEntityOwnerId($entityOwnerId);
            $partnershipsRequest->setEntityId($entityIds);
            $partnershipsRequest->setOrderBy([Entity::ID]);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__,false,$mode);
            if(!$redirectToApi)
            {
                return new Base\PublicCollection($response);
            }
        }
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->whereIn(Entity::ENTITY_ID, $entityIds)
                     ->where(Entity::ENTITY_OWNER_ID, $entityOwnerId)
                     ->orderBy(Entity::ID)
                     ->get();
    }

    /**
     * Fetch merchant access maps in sync for given entityId and entityOwnerId.
     * It fails if data is not in sync in test and live DB.
     *
     * @param   array  $entityIds       the entity or application IDs
     * @param   string  $entityOwnerId  the partner's merchant ID
     * @return  Base\PublicCollection
     * @throws  LogicException
     */
    public function fetchAccessMapsInSyncOrFail(array $entityIds, string $entityOwnerId) : Base\PublicCollection
    {
        $liveEntities = $this->fetchAllMappingsByEntityIdAndEntityOwnerId($entityIds, $entityOwnerId, 'live');
        $testEntities = $this->fetchAllMappingsByEntityIdAndEntityOwnerId($entityIds, $entityOwnerId, 'test');

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
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsAccessMapDTO();
            $partnershipsRequest->setFields([Entity::MERCHANT_ID]);
            $partnershipsRequest->setEntityOwnerId($partnerId);
            [$redirectToApi,$response]=$this->fetchMerchantAccessMapsOnFilter($partnershipsRequest,__FUNCTION__);
            if(!$redirectToApi)
            {
                return new PublicCollection($response);
            }
        }
        return $this->newQuery()
                    ->select(Entity::MERCHANT_ID)
                    ->where(Entity::ENTITY_OWNER_ID, $partnerId)
                    ->get();
    }

    public function getSubmerchantIDsOfAPartner(string $partnerId, $params)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Entity::MERCHANT_ID)
            ->where(Entity::ENTITY_OWNER_ID, $partnerId);

        if(empty($params[Merchant\Constants::WITHOUT_TAGS]) === false)
        {
            $tags = array_unique(array_map('mb_strtolower', array_map('str_slug', $params[Merchant\Constants::WITHOUT_TAGS])));

            $tagsTable = 'tagging_tagged';

            $query->whereNotIn(
                'merchant_access_map.merchant_id',
                function($query)
                use ($tags, $tagsTable) {
                    $query->select($tagsTable . '.taggable_id')
                        ->from($tagsTable)
                        ->where($tagsTable . '.taggable_type', '=', E::MERCHANT)
                        ->whereIn($tagsTable . '.tag_slug', $tags);
                });
        }

        $query->groupBy(Entity::MERCHANT_ID);

        return $query->get()->pluck(Entity::MERCHANT_ID);
    }

    public function getSubmIdsFromEntityOwnerIds(array $entityOwnerIds, $limit = null)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
        $query = $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                      ->select(Base\PublicEntity::MERCHANT_ID)
                      ->whereIn(Entity::ENTITY_OWNER_ID, $entityOwnerIds)
                      ->distinct();

        if (empty($limit) === false)
        {
            $query->take($limit);
        }

        return $query->get()
                     ->pluck(Base\PublicEntity::MERCHANT_ID)
                     ->toArray();
    }

    public function getSubMerchantsFromEntityOwnerId(string $entityOwnerId, $limit = null, $lastProcessedId = null)
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
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

    private function fetchMerchantAccessMapsOnFilter(PartnershipsAccessMapDTO $partnershipsDTO,$function=null, $fetchSingleEntity = false,$mode=null): array
    {
        $redirectFlag = false; // Flag for redirection decision
        $response = null;
        try {
            $partnershipResponse = $this->app['partnerships']->fetchMerchantAccessMapsOnFilter($partnershipsDTO,$function,$mode);

            // Decide response based on conditions and `fetchSingleEntity` flag
            $response = empty($partnershipResponse)
                ? ($fetchSingleEntity ? null : [])
                : ($fetchSingleEntity ? $partnershipResponse[0] : $partnershipResponse);
        }
        catch (\Exception $e) {
            // If an exception is caught, enable redirection and log the exception
            $redirectFlag = true;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNERSHIPS_ACCESS_MAP_LIST_ERROR);
        }

        return [$redirectFlag, $response];
    }

     private function fetchMerchantCount($partnerId, $fetchSingleEntity = false): array
    {
        $redirectFlag = false;
        $response = null;
        try {
            $partnershipResponse = $this->app['partnerships']->getSubMerchantCount($partnerId);

            $response = empty($partnershipResponse)
                ? ($fetchSingleEntity ? null : 0)
                : ($fetchSingleEntity ? $partnershipResponse[0] : $partnershipResponse);
        }
        catch (\Exception $e) {
            $redirectFlag = true;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNERSHIPS_ACCESS_MAP_SUBMERCHANT_COUNT_ERROR);
        }

        return [$redirectFlag, $response];
    }

    private function fetchAccessMapsForSubmerchant($subMerchantId, $appType, $fetchSingleEntity = false): array
    {
        $redirectFlag = false;
        $response = null;
        try {
            $partnershipResponse = $this->app['partnerships']->getMappingByApplicationType($subMerchantId, $appType);
            $response = empty($partnershipResponse)
                ? ($fetchSingleEntity ? null : [])
                : ($fetchSingleEntity ? $partnershipResponse[0] : $partnershipResponse);
        } catch (\Exception $e) {
            $redirectFlag = true;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNERSHIPS_MAPPING_BY_APPLICATION_TYPE_ERROR);
        }

        return [$redirectFlag, $response];
    }



}
