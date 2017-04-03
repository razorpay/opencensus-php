<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;
use RZP\Models\Invoice\Entity as InvoiceEntity;

class Expiring extends Base
{
    public function __construct(InvoiceEntity $invoice)
    {
        $this->invoice = $invoice;

        $this->event = Event::INVOICE_EXPIRING;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.expiring');

        return $this;
    }
}
