<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array             $input
     * @param Merchant\Entity   $merchant
     * @param Base\PublicEntity $entity
     * @param Item\Entity       $item
     *
     * @return Entity
     */
    public function create(
        array $input,
        Merchant\Entity $merchant,
        Base\PublicEntity $entity,
        Item\Entity $item)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_REQUEST,
            [
                'input'         => $input,
                'entity_id'    => $entity->getId(),
                'item_id'       => $item->getId(),
            ]);

        $lineItem = (new Entity)->build($input);

        $this->setLineItemAssociations($lineItem, $merchant, $entity, $item);

        $this->repo->saveOrFail($lineItem);

        return $lineItem;
    }

    public function getTotalAmountFromLineItems(array $lineItems)
    {
        $totalAmount = 0;

        array_map(function($lineItem) use (& $totalAmount)
        {
            $quantity = $lineItem->getQuantity();
            $amount   = $lineItem->item->getAmount();

            $totalAmount += ($amount * $quantity);
        }, $lineItems);

        return $totalAmount;
    }

    protected function setLineItemAssociations(
        Entity $lineItem, Merchant\Entity $merchant, Base\PublicEntity $entity, Item\Entity $item)
    {
        $lineItem->entity()->associate($entity);

        $lineItem->item()->associate($item);

        $lineItem->merchant()->associate($merchant);
    }
}
