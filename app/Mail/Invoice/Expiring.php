<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;

class Expiring extends Base
{
    public function __construct(Invoice\Entity $invoice)
    {
        $this->invoice = $invoice;

        $this->event = Event::INVOICE_EXPIRING;
    }

    protected function getView()
    {
        return 'emails.invoice.customer.expiring';
    }
}
