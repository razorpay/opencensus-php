<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;
use RZP\Models\Invoice\Entity as InvoiceEntity;

class Paid extends Base
{
    public function __construct(InvoiceEntity $invoice)
    {
        parent::__construct($invoice);

        $this->event = Event::INVOICE_PAID;
    }
}
