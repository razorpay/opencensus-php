<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Entity as InvoiceEntity;

class Expired extends Base
{
    public function __construct(InvoiceEntity $invoice)
    {
        parent::__construct($invoice);

        $this->event = Event::INVOICE_EXPIRED;
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }
}
