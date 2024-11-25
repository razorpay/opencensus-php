<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Constants as AppConstants;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = AppConstants\Entity::PARTNER_CONFIG;

    protected $appFetchParamRules = [
        Entity::ENTITY_ID        => 'sometimes|string|size:14',
        Entity::ORIGIN_ID        => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE      => 'filled|string',
        Entity::DEFAULT_PLAN_ID  => 'sometimes|string|size:14',
        Entity::IMPLICIT_PLAN_ID => 'sometimes|string|size:14',
        Entity::EXPLICIT_PLAN_ID => 'sometimes|string|size:14',
        Entity::COMMISSION_MODEL => 'sometimes|string|custom',
    ];

    /**
     * @param string $appId
     *
     * @param string|null $mode
     * @return null|Entity
     */
    public function getApplicationConfig(string $appId)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsConfigDTO();
            $partnershipsRequest->setEntityId($appId);
            $partnershipsRequest->setEntityType(Constants::APPLICATION);
            $partnershipsRequest->setOriginId("NULL");
            $partnershipsRequest->setOriginType("NULL");
            $partnershipsRequest->setLimit(1);
            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest,true);
            if($redirectToApi===false)
            {
                $this->trace->info(TraceCode::PARTNERSHIPS_RESPONSE,[
                    "partnerships"=>$response,
                ]);
                return $response;
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $appId)
                    ->where(Entity::ENTITY_TYPE, Constants::APPLICATION)
                    ->whereNull(Entity::ORIGIN_ID)
                    ->whereNull(Entity::ORIGIN_TYPE)
                    ->first();
    }

    /**
     * @param string $appId
     * @param string $subMerchantId
     *
     * @return null|Entity
     */
    public function getSubMerchantConfig(string $appId, string $subMerchantId)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsConfigDTO();
            $partnershipsRequest->setEntityId($subMerchantId);
            $partnershipsRequest->setEntityType(Constants::MERCHANT);
            $partnershipsRequest->setOriginId($appId);
            $partnershipsRequest->setOriginType(Constants::APPLICATION);
            $partnershipsRequest->setLimit(1);
            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest,true);
            if($redirectToApi===false)
            {
                return $response;
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->where(Entity::ORIGIN_TYPE, Constants::APPLICATION)
                    ->where(Entity::ORIGIN_ID, $appId)
                    ->first();
    }

    /**
     * Fetch default and overridden configs of the OAuth applications
     *
     * @param   array           $appIds
     * @param   string|null     $mode
     * @return  Base\PublicCollection
     */
    public function fetchAllConfigForApps(array $appIds, string $mode = null)
    {
        if (empty($appIds) === true)
        {
            return new Base\PublicCollection;
        }

        $defaultConfig = function ($query) use ($appIds)
        {
            $query->whereIn(Entity::ENTITY_ID, $appIds)
                  ->where(Entity::ENTITY_TYPE, Constants::APPLICATION)
                  ->whereNull(Entity::ORIGIN_ID)
                  ->whereNull(Entity::ORIGIN_TYPE);
        };

        $overriddenConfig = function ($query) use ($appIds)
        {
            $query->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                  ->where(Entity::ORIGIN_TYPE, Constants::APPLICATION)
                  ->whereIn(Entity::ORIGIN_ID, $appIds);
        };

        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->where($defaultConfig)
                     ->orWhere($overriddenConfig)
                     ->orderBy(Entity::CREATED_AT, 'desc')
                     ->orderBy(Entity::ID, 'desc')
                     ->get();
    }

    /**
     * Fetch default and overridden configs of the pure platform partner
     *
     * @param   string          $partnerId
     * @param   string|null     $mode
     * @return  Base\PublicCollection
     */
    public function fetchAllConfigForPlatformPartner(string $partnerId, string $mode = null)
    {
        $defaultConfig = function ($query) use ($partnerId)
        {
            $query->where(Entity::ENTITY_ID, $partnerId)
                  ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                  ->whereNull(Entity::ORIGIN_ID)
                  ->whereNull(Entity::ORIGIN_TYPE);
        };

        $overriddenConfig = function ($query) use ($partnerId)
        {
            $query->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                  ->where(Entity::ORIGIN_TYPE, Constants::MERCHANT)
                  ->where(Entity::ORIGIN_ID,   $partnerId);
        };

        $query = ($mode === null) ? $this->newQuery() : $this->newQueryWithConnection($mode);
        return $query->where($defaultConfig)
                     ->orWhere($overriddenConfig)
                     ->orderBy(Entity::CREATED_AT, 'desc')
                     ->orderBy(Entity::ID, 'desc')
                     ->get();
    }

    /**
     * @param string $partnerId
     * @param string $subMerchantId
     *
     * @return null|Entity
     */
    public function getPartnerSubMerchantConfig(string $partnerId, string $subMerchantId)
    {
        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
        if($switchOverExperimentEnabled)
        {
            $partnershipsRequest= new PartnershipsConfigDTO();
            $partnershipsRequest->setEntityId($subMerchantId);
            $partnershipsRequest->setEntityType(Constants::MERCHANT);
            $partnershipsRequest->setOriginId($partnerId);
            $partnershipsRequest->setOriginType(Constants::MERCHANT);
            $partnershipsRequest->setLimit(1);
            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest,true);
            if($redirectToApi===false)
            {
                return $response;
            }
        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->where(Entity::ORIGIN_TYPE, Constants::MERCHANT)
                    ->where(Entity::ORIGIN_ID, $partnerId)
                    ->first();
    }

    /**
     * @param string $partnerId
     *
     * @return null|Entity
     */
    public function getPlatformPartnerDefaultConfig(string $partnerId)
    {
//        $switchOverExperimentEnabled=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
//        if(!$switchOverExperimentEnabled)
//        {
//            $partnershipsRequest= new PartnershipsConfigDTO();
//            $partnershipsRequest->setEntityId($partnerId);
//            $partnershipsRequest->setEntityType(Constants::MERCHANT);
//            $partnershipsRequest->setOriginId("NULL");
//            $partnershipsRequest->setOriginType("NULL");
//            $partnershipsRequest->setLimit(1);
//            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest,true);
//            if($redirectToApi===false)
//            {
//                return $response;
//            }
//        }

        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->where(Entity::ENTITY_ID, $partnerId)
                    ->whereNull(Entity::ORIGIN_ID)
                    ->whereNull(Entity::ORIGIN_TYPE)
                    ->first();
    }

    /**
     * Fetch default and overridden configs in sync for given applicationIDs.
     * It fails if data is not in sync in test and live DB.
     *
     * @param   array   $appIds
     *
     * @return  Base\PublicCollection
     * @throws  LogicException
     */
    public function fetchAllConfigsInSyncOrFail(array $appIds) : Base\PublicCollection
    {
        $liveEntities = $this->fetchAllConfigForApps($appIds, 'live');
        $testEntities = $this->fetchAllConfigForApps($appIds, 'test');
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
     * @param $attribute
     * @param $type
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    public function validateCommissionModel($attribute, $type)
    {
        CommissionModel::validate($type);
    }


    public function fetchOverriddenConfigsByMerchantId(array $appIds, string $subMerchantId)
    {

//        $flag=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
//        if($flag===true)
//        {
//            $partnershipsRequest= new PartnershipsConfigDTO();
//            $partnershipsRequest->setEntityId($subMerchantId);
//            $partnershipsRequest->setEntityType(Constants::MERCHANT);
//            $partnershipsRequest->setOriginId($appIds);
//            $partnershipsRequest->setOrderBy([Entity::CREATED_AT,Entity::ID]);
//            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest);
//            if($redirectToApi===false)
//            {
//                return new Base\PublicCollection($response);
//            }
//        }

        return $this->newQuery()
                    ->whereIn(Entity::ORIGIN_ID, $appIds)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    public function fetchDefaultConfigForAppIds(array $appIds) {

//        $flag=$this->app['partnerships']->evaluateSwitchOverPartnershipsSplitzExperiment(__FUNCTION__);
//        if($flag===true)
//        {
//            $partnershipsRequest= new PartnershipsConfigDTO();
//            $partnershipsRequest->setEntityId($appIds);
//            $partnershipsRequest->setEntityType(Constants::MERCHANT);
//            $partnershipsRequest->setOriginId("NULL");
//            $partnershipsRequest->setOriginType("NULL");
//            $partnershipsRequest->setOrderBy([Entity::CREATED_AT,Entity::ID]);
//            [$redirectToApi,$response]=$this->fetchPartnerConfigOnFilter($partnershipsRequest);
//            if($redirectToApi===false)
//            {
//                return new Base\PublicCollection($response);
//            }
//        }

        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $appIds)
                    ->where(Entity::ENTITY_TYPE, Constants::APPLICATION)
                    ->whereNull(Entity::ORIGIN_ID)
                    ->whereNull(Entity::ORIGIN_TYPE)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    private function fetchPartnerConfigOnFilter(PartnershipsConfigDTO $partnershipsRequest, $fetchSingleEntity = false): array
    {
        $redirectFlag = false; // Flag for redirection decision
        $response = null;
        try
        {
            $partnershipResponse = $this->app['partnerships']->fetchPartnerConfigOnFilter($partnershipsRequest);

            // Decide response based on conditions and `fetchSingleEntity` flag
            $response = empty($partnershipResponse)
                ? ($fetchSingleEntity ? null : [])
                : ($fetchSingleEntity ? $partnershipResponse[0] : $partnershipResponse);
        }
        catch (\Exception $e)
        {
            // If an exception is caught, disable redirection and log the exception
            $redirectFlag = true;
            $this->trace->traceException($e, Trace::ERROR, TraceCode::PARTNERSHIPS_CONFIG_LIST_ERROR);
        }
        return [$redirectFlag, $response];
    }

}
