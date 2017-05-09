<?php

namespace RZP\Models\Invoice\Calculator;

use RZP\Models\Invoice;

class Amount
{
    private $invoice;

    public function __construct(Invoice\Entity $invoice)
    {
        $this->invoice = $invoice;
    }

    public function calculateAndSetAmounts()
    {
        $lineItems = $this->invoice->lineItems()->get();

        if ($lineItems->count() === 0)
        {
            if ($this->invoice->isTypeInvoice() === true)
            {
                $this->invoice->setAmountsToNull();
            }

            return;
        }

        $amount = $taxAmount = $netAmount = 0;

        foreach ($lineItems as $lineItem)
        {
            $amount    += $lineItem->getTotalAmount();
            $taxAmount += $lineItem->getTaxAmount();
            $netAmount += $lineItem->getNetAmount();
        }

        $this->invoice->setAmount($amount);
        $this->invoice->setTaxAmount($taxAmount);
        $this->invoice->setNetAmount($netAmount);

        $this->invoice->getValidator()
                      ->validateMaxAllowedAmount($amount);
    }
}
