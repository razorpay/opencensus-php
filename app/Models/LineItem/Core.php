<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Item;
use RZP\Exception;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    public function create(array $input, Invoice\Entity $invoice, Item\Entity $item)
    {
        $lineItem = (new Entity)->build($input);

        $lineItem->entity()->associate($invoice);

        $lineItem->item()->associate($item);

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
}
