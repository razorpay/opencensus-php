<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;
use RZP\Models\Invoice\Entity as InvoiceEntity;
use RZP\Models\Invoice\Type;

class Expiring extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment request from %s is expiring',
        Type::ECOD    => ' Payment request from %s is expiring',
        Type::INVOICE => ' Invoice from %s is expiring',
    ];

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

    protected function getSubjectTemplate()
    {
        $type =  $this->invoice->getType();

        return self::SUBJECT_TEMPLATES[$type];
    }
}
