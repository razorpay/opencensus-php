<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Partner\Metric;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_application';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::TYPE            => 'sometimes|string',
        Entity::APPLICATION_ID  => 'sometimes|string|size:14'
    ];

    /**
     * @param   string      $merchantId     The partner's MID for whom merchant Apps need to be fetched
     * @param   array       $types          Application types, ex., referred, managed
     * @param   string|null $mode           The connection mode
     * @param   bool        $withTrashed    Whether to include soft deleted results?
     *
     * @return  Base\PublicCollection
     */
    public function fetchMerchantApplications(
        string $merchantId, array $types = [], string $mode = null, bool $withTrashed = false, string $appId = null
    ) : Base\PublicCollection
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsMerchantApplicationsDTO();
            $partnershipsRequest->setMerchantId($merchantId);
            $partnershipsRequest->setType($types);
            $partnershipsRequest->setTrashed($withTrashed);
            if(!empty($appId)) {
                $partnershipsRequest->setApplicationId($appId);
            }
            $partnershipsRequest->setOrderBy([Entity::TYPE,Entity::ID]);
            [$redirectToApi,$response]=$this->fetchMerchantApplicationsOnFilter($partnershipsRequest,__FUNCTION__,false,$mode);
            if($redirectToApi===false)
            {
                return new Base\PublicCollection($response);
            }
        }
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        $query = $query->merchantId($merchantId);

        if (empty($types) === false)
        {
            $query = $query->whereIn(Entity::TYPE, $types);
        }
        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }
        if (empty($appId) === false)
        {
            $query = $query->where(Entity::APPLICATION_ID, $appId);
        }

        return $query->orderBy(Entity::TYPE)->orderBy(Entity::ID)->get();
    }

    /**
     * Fetch merchant applications in sync for given merchantIDs and given types.
     * It fails if data is not in sync in test and live DB.
     * @param   string  $merchantId     The partner's MID for whom merchant Apps need to be fetched
     * @param   array   $types          Application types, ex., referred, managed
     * @param   bool    $withTrashed    Whether to include soft deleted results?
     *
     * @return  Base\PublicCollection
     *
     * @throws  LogicException
     */
    public function fetchMerchantAppInSyncOrFail(
        string $merchantId, array $types = [], bool $withTrashed = false
    ) : Base\PublicCollection
    {
        $liveEntities = $this->fetchMerchantApplications($merchantId, $types, 'live', $withTrashed);
        $testEntities = $this->fetchMerchantApplications($merchantId, $types, 'test', $withTrashed);
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

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantApplication(string $entityId, string $entityType) : Base\PublicCollection
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsMerchantApplicationsDTO();
            switch ($entityType) {
                case Entity::TYPE:
                    $partnershipsRequest->setType([$entityId]);
                    break;
                case Entity::APPLICATION_ID:
                    $partnershipsRequest->setApplicationId($entityId);
                    break;
                case Entity::MERCHANT_ID:
                    $partnershipsRequest->setMerchantId($entityId);
                    break;
            }
            if(!(empty($partnershipsRequest->getType()) && empty($partnershipsRequest->getApplicationId()) && empty($partnershipsRequest->getMerchantId()))) {
                [$redirectToApi, $response] = $this->fetchMerchantApplicationsOnFilter($partnershipsRequest,__FUNCTION__);
                if ($redirectToApi === false) {
                    return new Base\PublicCollection($response);
                }
            } else {
                $this->trace->count(Metric::SWITCH_OVER_PARTNERSHIPS_MERCHANT_APPLICATION_UNKNOWN_ENTITY,[
                    'entityType' => $entityType,
                    'entityId' => $entityId,
                ]);
            }
        }
        return $this->newQuery()
                    ->where($entityType, $entityId)
                    ->get();
    }

    /**
     * @param   array   $applicationIds Application IDs of merchant application
     *
     * @return  Base\PublicCollection
     */
    public function fetchMerchantApplicationByAppIds(array $applicationIds) : Base\PublicCollection
    {
        $this->app['partnerships']->pushMetricForPartnershipsSwitchOver(__FUNCTION__);
//        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);;
//        if($switchOverExperimentEnabled)
//        {
//            $partnershipsRequest= new PartnershipsMerchantApplicationsDTO();
//            $partnershipsRequest->setApplicationId($applicationIds);
//            [$redirectToApi,$response]=$this->fetchMerchantApplicationsOnFilter($partnershipsRequest,__FUNCTION__);
//            if(!$redirectToApi)
//            {
//                return new Base\PublicCollection($response);
//            }
//        }
        return $this->newQuery()
                    ->whereIn(Entity::APPLICATION_ID, $applicationIds)
                    ->get();
    }

    /**
     * @param   string   $applicationId Application ID of merchant application
     *
     * @return  null|Entity
     */
    public function fetchMerchantApplicationByAppIdAndType(string $applicationId, string $type) : Entity|null
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsMerchantApplicationsDTO();
            $partnershipsRequest->setType([$type]);
            $partnershipsRequest->setApplicationId($applicationId);
            [$redirectToApi,$response]=$this->fetchMerchantApplicationsOnFilter($partnershipsRequest,__FUNCTION__,true);
            if($redirectToApi===false)
            {
                return $response;
            }
        }
         return $this->newQuery()
                     ->where(Entity::APPLICATION_ID, $applicationId)
                     ->where(Entity::TYPE, $type)
                     ->first();
    }

    /**
     * Restores the merchant applications for given appIds
     * @param   array           $deletedAppIds  The application_id of merchant applications
     * @param   string|null     $mode
     * @return  void
     */
    public function restoreDeletedApps(array $deletedAppIds, string $mode = null)
    {
        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->whereIn(Entity::APPLICATION_ID, $deletedAppIds)
                     ->withTrashed()
                     ->update([
                         Entity::DELETED_AT => null,
                     ]);
    }

    private function fetchMerchantApplicationsOnFilter(PartnershipsMerchantApplicationsDTO $partnershipsRequest,$function=null,$fetchSingleEntity = false,$mode=null)
    {
        $redirectFlag = false; // Flag for redirection decision
        $response = null;
        try
        {
            $partnershipResponse = $this->app['partnerships']->fetchMerchantApplicationsOnFilter($partnershipsRequest,$function,$mode);

            // Decide response based on conditions and `fetchSingleEntity` flag
            $response = empty($partnershipResponse)
                ? ($fetchSingleEntity ? null : [])
                : ($fetchSingleEntity ? $partnershipResponse[0] : $partnershipResponse);
        }
        catch (\Exception $e)
        {
            // If an exception is caught, disable redirection and log the exception
            $redirectFlag = true;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNERSHIPS_ACCESS_MAP_LIST_ERROR);
        }

        return [$redirectFlag, $response];
    }
}
