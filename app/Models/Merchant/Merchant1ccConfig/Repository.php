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
            ->orderBy(Entity::UPDATED_AT, 'desc')
            ->first();
    }

    public function findByMerchantAndConfigArray($merchantId, $configs)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereIn(Entity::CONFIG, $configs)
            ->where(Entity::DELETED_AT, '=', null)
            ->orderBy(Entity::UPDATED_AT, 'desc')
            ->get();
    }

    public function findAllByMerchantAndConfigType($merchantId, $config)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::CONFIG, '=', $config)
            ->where(Entity::DELETED_AT, '=', null)
            ->get();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::DELETED_AT, '=', null);
    }
}
