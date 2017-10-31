<?php

namespace RZP\Mail\Invoice;

use RZP\Models\Invoice\Type;

class Expired extends Base
{
    const SUBJECT_TEMPLATES = [
        Type::LINK    => ' Payment request of Rs. %s has expired (via Razorpay)',
        Type::ECOD    => ' Payment request of Rs. %s has expired (via Razorpay)',
        Type::INVOICE => ' Invoice from %s has expired',
    ];

    public function __construct(array $data)
    {
        parent::__construct($data);
    }

    protected function addHtmlView()
    {
        $this->view('emails.invoice.customer.notification');

        return $this;
    }
}
