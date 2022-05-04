<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_international_integrations';

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

}
