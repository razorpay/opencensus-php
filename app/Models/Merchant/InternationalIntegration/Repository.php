<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_international_integrations';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|alpha_num',
        Entity::INTEGRATION_ENTITY => 'sometimes|string|max:20'
    ];

    public function getByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DELETED_AT, null)
            ->get();
    }

    public function getByMerchantIdAndIntegrationEntity($merchantId, $integrationEntity)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::INTEGRATION_ENTITY, $integrationEntity)
            ->where(Entity::DELETED_AT, null)
            ->first();
    }

    public function  getByIntegrationEntityAndKey($integrationEntity, $integrationKey)
    {
        return $this->newQuery()
            ->where(Entity::INTEGRATION_KEY,$integrationKey)
            ->where(Entity::INTEGRATION_ENTITY,$integrationEntity)
            ->first();
    }

    public function getByIntegrationKey($integrationKey)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::INTEGRATION_KEY, $integrationKey)
            ->where(Entity::DELETED_AT, null)
            ->get();
    }

}
