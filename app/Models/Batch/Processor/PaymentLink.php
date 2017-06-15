<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Models\Batch;

class PaymentLink extends Base
{
    protected function processEntry(array & $entry)
    {
        $input = Batch\Helpers\PaymentLink::getEntityInput($entry);

        $invoice = (new Invoice\Core)->create(
                        $input, $this->merchant, null, $this->batch);

        // Update the entry with output values

        $entry[Batch\Header::PAYMENT_LINK_ID] = $invoice->getPublicId();
    }
}
