<?php

namespace RZP\Models\Partner\Config;

use RZP\Models\Base;
use RZP\Constants as AppConstants;

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
    ];

    /**
     * @param string $appId
     *
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
     * @param array $appIds
     *
     * @return mixed
     */
    public function fetchAllConfigForApps(array $appIds)
    {
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

        return $this->newQuery()
                    ->where($defaultConfig)
                    ->orWhere($overriddenConfig)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->orderBy(Entity::ID, 'desc')
                    ->get();
    }
}
