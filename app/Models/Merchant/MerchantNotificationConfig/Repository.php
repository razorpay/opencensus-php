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

    public function findByMerchantIdAndNotificationType(string $merchantId, $notificationType)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
                    ->get();
    }

    public function getTotalEnabledConfigsCountForGivenNotificationType($notificationType)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
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
        $query = $this->newQuery()
                      ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED);

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
                      ->where(Entity::MERCHANT_ID, '=', $merchantId)
                      ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED);

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

    public function getEnabledConfigsForNotificationType(string $notificationType, $limit = 1000)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
                    ->latest()
                    ->limit($limit)
                    ->get();
    }

    public function getEnabledConfigsForNotificationTypeUsingLastFetchedConfig($notificationType, $limit, $lastFetchedConfig = null)
    {
        $query = $this->newQuery()
                      ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                      ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
                      ->orderByRaw(Entity::MERCHANT_ID . ' desc,' . Entity::CREATED_AT . ' desc')
                      ->limit($limit);

        if ($lastFetchedConfig !== null)
        {
            $query->whereRaw('(' . Entity::MERCHANT_ID . ',' . Entity::CREATED_AT . ')' . '<' . '(?,?)',
                             [$lastFetchedConfig->getMerchantId(), $lastFetchedConfig->getCreatedAt()]);
        }

        return $query->get();
    }

    public function getEnabledConfigsFromMerchantIdsAndNotificationType($merchantIds, $notificationType)
    {
        return $this->newQuery()
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->get();
    }
}
