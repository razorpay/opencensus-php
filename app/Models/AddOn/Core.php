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
    public function create(array $input, Subscription\Entity $subscription)
    {
        $this->trace->info(
            TraceCode::ADD_ON_CREATE_REQUEST,
            [
                'input' => $input,
            ]);

        $addOn = (new Entity)->build($input);

        $this->repo->transaction(
            function() use ($addOn, $input, $subscription)
            {
                $merchant = $subscription->merchant;

                $item = $this->createItemForAddOn($input, $merchant);

                $this->createAddOnAssociations($addOn, $merchant, $item, $subscription);

                $this->repo->saveOrFail($addOn);
            });

        return $addOn;
    }

    protected function createItemForAddOn(array $input, Merchant\Entity $merchant)
    {
        if (empty($input[Entity::ITEM_ID]) === false)
        {
            $item = $this->repo->item->findByPublicIdAndMerchantForType(
                                                            $input[Entity::ITEM_ID],
                                                            $merchant,
                                                            Item\Type::ADD_ON);
        }
        else
        {
            $itemInput = [
                Item\Entity::NAME       => $input[Entity::NAME],
                Item\Entity::AMOUNT     => $input[Entity::AMOUNT],
                Item\Entity::CURRENCY   => $input[Entity::CURRENCY],
                Item\Entity::TYPE       => Item\Type::ADD_ON,
            ];

            $item = (new Item\Core)->create($itemInput, $merchant);
        }

        return $item;
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
