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

    protected $entityFetchParamRules = [
        // This is also used to fetch all
        // addons of a subscription in processor
        Entity::SUBSCRIPTION_ID     => 'filled|string|public_id',
        Entity::INVOICE_ID          => 'filled|string|public_id',
    ];

    protected $signedIds = [
        Entity::SUBSCRIPTION_ID,
        Entity::INVOICE_ID,
    ];

    protected $expands = [
        Entity::ITEM
    ];

    public function getUnusedAddonsForSubscription(Subscription\Entity $subscription)
    {
        return $this->newQuery()
                    ->whereNull(Entity::INVOICE_ID)
                    ->where(Entity::SUBSCRIPTION_ID, '=', $subscription->getId())
                    ->with(Constants\Entity::ITEM)
                    ->get();
    }
}
