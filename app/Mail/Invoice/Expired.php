<?php

namespace RZP\Mail\Invoice;

class Expired extends Base
{
    public function __construct(Invoice\Entity $invoice)
    {
        parent::__construct($invoice);

        $this->event = Event::INVOICE_EXPIRED;
    }

    protected function getView()
    {
        return 'emails.invoice.customer.notification';
    }
}
