<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Invoice;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers;

class PaymentLink extends Base
{
    protected function processEntry(array & $entry)
    {
        $input = Helpers\PaymentLink::getEntityInput($entry, $this->params);

        $invoice = (new Invoice\Core)->create(
                        $input, $this->merchant, null, $this->batch);

        // Update the entry with output values

        $entry[Header::PAYMENT_LINK_ID]     = $invoice->getPublicId();
        $entry[Header::PAYMENT_LINK_STATUS] = $invoice->getStatus();
        $entry[Header::SHORT_URL]           = $invoice->getShortUrl();
    }
}
