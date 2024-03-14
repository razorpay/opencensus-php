<?php

namespace RZP\Models\Notification;

use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'notification';

    public function findByOrderId(string $orderId)
    {
        $notification = $this->newQuery()
                           ->where(Entity::ORDER_ID, '=', $orderId)
                           ->first();

        return $notification;
    }

    public function findDeliveredNotificationByOrderId(string $orderId)
    {
        $notification = $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId)
            ->where(Entity::STATUS, '=', 'delivered')
            ->first();

        return $notification;
    }

    public function findByOrderIdAndMerchantId(string $orderId, Merchant\Entity $merchant)
    {
        $notification = $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId)
            ->merchantId($merchant->getId())
            ->first();

        return $notification;
    }

    public function fetchNotificationCount(string $orderId)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId)
            ->count();
    }

    public function fetchSuccessfulNotificationCount(string $orderId)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId)
            ->where(Entity::STATUS, '=', 'delivered')
            ->count();
    }

    public function findById($id): Entity
    {
        $notification = $this->newQuery()
            ->where(Entity::ID, $id)
            ->first();

        return $notification;
    }
}
