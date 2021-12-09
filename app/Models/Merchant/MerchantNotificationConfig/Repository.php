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

    public function findByMerchantIdNotificationTypeAndMode(string $merchantId, $notificationType, $mode = 'ALL')
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
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

    public function getEnabledConfigsForNotificationType(string $notificationType)
    {
        $midColumn          = $this->repo->merchant_notification_config->dbColumn(Entity::MERCHANT_ID);
        $emailIdColumn      = $this->repo->merchant_notification_config->dbColumn(Entity::NOTIFICATION_EMAILS);
        $mobileNumberColumn = $this->repo->merchant_notification_config->dbColumn(Entity::NOTIFICATION_MOBILE_NUMBERS);

        return $this->newQuery()
                    ->select($midColumn, $emailIdColumn, $mobileNumberColumn)
                    ->where(Entity::CONFIG_STATUS, '=', Status::ENABLED)
                    ->where(Entity::NOTIFICATION_TYPE, '=', $notificationType)
                    ->get();
    }
}
