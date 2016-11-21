<?php

namespace RZP\Models\LineItem;

use RZP\Models\Base;
use RZP\Models\Invoice;
use RZP\Models\Item;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array          $input
     * @param Invoice\Entity $invoice
     * @param Item\Entity    $item
     *
     * @return Entity
     */
    public function create(array $input, Invoice\Entity $invoice, Item\Entity $item)
    {
        $this->trace->info(
            TraceCode::LINE_ITEM_CREATE_REQUEST,
            [
                'input'         => $input,
                'invoice_id'    => $invoice->getId(),
                'item_id'       => $item->getId(),
            ]);

        $lineItem = (new Entity)->build($input);

        $lineItem->entity()->associate($invoice);

        $lineItem->item()->associate($item);

        $lineItem->merchant()->associate($this->merchant);

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
