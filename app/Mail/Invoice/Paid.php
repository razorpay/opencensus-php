<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;

class Paid extends Base
{
    public function __construct(Invoice\Entity $invoice)
    {
        parent::__construct($invoice);

        $this->event = Event::INVOICE_PAID;
    }
}
