<?php

namespace RZP\Models\AddOn;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Models\Plan\Subscription;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Subscription\Entity $subscription): Entity
    {
        $this->trace->info(
            TraceCode::ADD_ON_CREATE_REQUEST,
            [
                'input' => $input,
                'subscription_id' => $subscription->getId()
            ]);

        $addOn = (new Entity)->build($input);

        $this->repo->transaction(
            function() use ($addOn, $input, $subscription)
            {
                $merchant = $subscription->merchant;

                $item = (new Item\Core)->createItemForType($input, $merchant, Item\Type::ADD_ON);

                $this->createAddOnAssociations($addOn, $merchant, $item, $subscription);

                $this->repo->saveOrFail($addOn);
            });

        return $addOn;
    }

    protected function createAddOnAssociations(
        Entity $addOn,
        Merchant\Entity $merchant,
        Item\Entity $item,
        Subscription\Entity $subscription)
    {
        $addOn->merchant()->associate($merchant);
        $addOn->item()->associate($item);
        $addOn->subscription()->associate($subscription);
    }
}
