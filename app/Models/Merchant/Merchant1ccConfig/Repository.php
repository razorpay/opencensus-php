<?php

namespace RZP\Models\Merchant\Merchant1ccConfig;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_1cc_configs';

    public function findByMerchantAndConfigType($merchantId, $config)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::CONFIG, '=', $config)
            ->where(Entity::DELETED_AT, '=', null)
            ->first();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::DELETED_AT, '=', null);
    }
}
