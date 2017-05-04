<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice;
use RZP\Models\Invoice\Type;

class Expiring extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment request from %s is expiring',
        Type::ECOD    => ' Payment request from %s is expiring',
        Type::INVOICE => ' Invoice from %s is expiring',
    ];

    public function __construct(array $invoice, array $invoiceData)
    {
        parent::__construct($invoice, $invoiceExpiring);
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.expiring');

        return $this;
    }

    protected function getSubjectTemplate()
    {
        $type =  $this->invoice['type'];

        return self::SUBJECT_TEMPLATES[$type];
    }
}
