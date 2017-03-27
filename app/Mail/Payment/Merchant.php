<?php

namespace RZP\Mail\Payment;

class Merchant extends Base
{
    public function __construct(string $event, array $data)
    {
        parent::__construct($event, $data);

        $this->metadata = Metadata::MERCHANT[$this->event];
    }

    public function isCustomerReceiptEmail()
    {
        return false;
    }

    protected function getSubject()
    {
        $subject = parent::getSubject();

        $subject = "Razorpay | $subject";

        return $subject;
    }

    protected function getTo()
    {
        return $this->data['merchant']['email'];
    }
}
