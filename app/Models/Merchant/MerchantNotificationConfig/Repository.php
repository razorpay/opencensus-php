<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::MERCHANT_NOTIFICATION_CONFIG;

    public function findByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function findByMerchantIdAndMode(string $merchantId, string $mode)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::MODE, '=', $mode)
                    ->get();
    }

    public function getTotalEnabledConfigsCount()
    {
        return $this->newQuery()
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->count();
    }

    public function getTotalEnabledConfigsCountByMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->count();
    }

    public function getEnabledConfigs($limit)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->latest()
                    ->limit($limit)
                    ->get();
    }

    public function getEnabledConfigsByMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->latest()
                    ->get();
    }

    public function getEnabledConfigsByTime(int $startTime, int $endTime = null)
    {
        $query = $this->newQuery();

        if (is_null($endTime) === true)
        {
            $query->where(Entity::CREATED_AT, '>=', $startTime);
        }
        else
        {
            $query->whereBetween(Entity::CREATED_AT, [$startTime, $endTime]);
        }

        return $query->latest()->get();
    }

    public function getEnabledConfigsForMerchantByTime(string $merchantId, int $startTime, int $endTime = null)
    {
        $query = $this->newQuery()
                  ->where(Entity::MERCHANT_ID, '=', $merchantId);

        if (is_null($endTime) === true)
        {
            $query->where(Entity::CREATED_AT, '>=', $startTime);
        }
        else
        {
            $query->whereBetween(Entity::CREATED_AT, [$startTime, $endTime]);
        }

        return $query->latest()->get();
    }
}
