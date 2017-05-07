<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Models\Plan\Subscription;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Subscription\Entity $subscription): Entity
    {
        $this->trace->info(
            TraceCode::ADDON_CREATE_REQUEST,
            [
                'input'             => $input,
                'subscription_id'   => $subscription->getId()
            ]);

        $addon = (new Entity)->build($input);

        $this->repo->transaction(
            function() use ($addon, $input, $subscription)
            {
                $merchant = $subscription->merchant;

                $item = (new Item\Core)->getOrCreateItemForType($input, $merchant, Item\Type::ADDON);

                $this->createAddonAssociations($addon, $merchant, $item, $subscription);

                $this->repo->saveOrFail($addon);
            });

        return $addon;
    }

    protected function createAddonAssociations(
        Entity $addon,
        Merchant\Entity $merchant,
        Item\Entity $item,
        Subscription\Entity $subscription)
    {
        $addon->merchant()->associate($merchant);
        $addon->item()->associate($item);
        $addon->subscription()->associate($subscription);
    }
}
