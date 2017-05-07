<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Models\Plan\Subscription;
use RZP\Constants;

class Repository extends Base\Repository
{
    protected $entity = 'addon';

    public function getUnusedAddonsForSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->whereNull(Entity::INVOICE_ID)
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->with(Constants\Entity::ITEM)
                    ->get();
    }

    public function getAllAddonsOfSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->get();
    }
}
