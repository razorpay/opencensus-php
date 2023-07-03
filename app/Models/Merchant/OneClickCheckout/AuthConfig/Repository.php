<?php

namespace RZP\Models\Merchant\OneClickCheckout\AuthConfig;

use RZP\Models\Base;
use RZP\Models\Merchant\OneClickCheckout\Constants;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_1cc_auth_configs';

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->first();
    }

    public function findByMerchantAndPlatform($merchantId, $platform)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::PLATFORM, '=', $platform)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->get();
    }

    public function findByConfig($merchantId, $platform, $config)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::PLATFORM, '=', $platform)
            ->where(Entity::CONFIG, '=', $config)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->first();
    }

    public function findMerchantIdByPlatformConfigValue(string $value, string $platform, string $config)
    {
        return $this->newQuery()
            ->where(Entity::PLATFORM, '=', $platform)
            ->where(Entity::CONFIG, '=', $config)
            ->where(Entity::VALUE, '=', $value)
            ->first();
    }


    public function deleteByMerchantAndPlatform($merchantId, $platform)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::PLATFORM, $platform)
            ->delete();
    }

    public function deleteByConfig($merchantId, $platform, $config)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::PLATFORM, '=', $platform)
            ->where(Entity::CONFIG, '=', $config)
            ->delete();
    }

    public function findLatestMerchantIdByPlatformConfigValue(string $value, string $platform, string $config)
    {
        return $this->newQuery()
            ->where(Entity::PLATFORM, '=', $platform)
            ->where(Entity::CONFIG, '=', $config)
            ->where(Entity::VALUE, '=', $value)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->latest()
            ->first();
    }
}
