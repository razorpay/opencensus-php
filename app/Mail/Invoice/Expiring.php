<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Expiring extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment request of %s %s is expiring (via Razorpay)',
        Type::ECOD    => ' Payment request of %s %s is expiring (via Razorpay)',
        Type::INVOICE => ' Invoice from %s is expiring',
    ];

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.expiring');

        return $this;
    }
}
