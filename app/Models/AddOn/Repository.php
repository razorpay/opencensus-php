<?php

namespace RZP\Models\AddOn;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Models\Plan\Subscription;
use RZP\Constants;

class Repository extends Base\Repository
{
    protected $entity = 'add_on';

    public function getUnusedAddOnsForSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->whereNull(Entity::INVOICE_ID)
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->with(Constants\Entity::ITEM)
                    ->get();
    }
}
