<?php

namespace RZP\Models\LineItem\Calculator;

use RZP\Models\LineItem;

/**
*
*/
class Amount
{
    private $lineItem;

    public function __construct(LineItem\Entity $lineItem)
    {
        $this->lineItem = $lineItem;
    }

    public function calculateAndSetAmounts()
    {
        $totalAmount = $this->lineItem->getAmount() * $this->lineItem->getQuantity();

        $this->lineItem->setTotalAmount($totalAmount);

        $this->lineItem->setTaxAmount(0);
        $this->lineItem->setNetAmount(0);
    }
}
