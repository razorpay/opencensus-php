<?php

namespace RZP\Mail\Invoice;

class Expired extends Base
{
    public function __construct(Invoice\Entity $invoice)
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
