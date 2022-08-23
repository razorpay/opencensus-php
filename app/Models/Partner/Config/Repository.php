<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception;
use RZP\Exception\LogicException;
use RZP\Models\Base;
use RZP\Constants as AppConstants;
use RZP\Trace\TraceCode;

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


    public function fetchOverriddenConfigsByMerchantId(array $appIds, string $subMerchantId) {
        return $this->newQuery()
                    ->whereIn(Entity::ORIGIN_ID, $appIds)
                    ->where(Entity::ENTITY_TYPE, Constants::MERCHANT)
                    ->where(Entity::ENTITY_ID, $subMerchantId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

    public function fetchDefaultConfigForAppIds(array $appIds) {
        return $this->newQuery()
                    ->whereIn(Entity::ENTITY_ID, $appIds)
                    ->where(Entity::ENTITY_TYPE, Constants::APPLICATION)
                    ->whereNull(Entity::ORIGIN_ID)
                    ->whereNull(Entity::ORIGIN_TYPE)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }

}
