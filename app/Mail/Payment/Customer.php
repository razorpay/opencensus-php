<?php

namespace RZP\Mail\Payment;

class Customer extends Base
{
    public function __construct(string $event, array $data)
    {
        parent::__construct($event, $data);

        $this->metadata = Metadata::CUSTOMER[$this->event];
    }

    public function isCustomerReceiptEmail()
    {
        return Event::isCustomerReceiptEmailRequired($this->event);
    }

    protected function getSubject()
    {
        $subject = parent::getSubject();

        if ($this->event === Event::CARD_SAVED)
        {
            $subject = "Card successfully saved with Razorpay";
        }

        return $subject;
    }

    protected function getTo()
    {
        return $this->data['customer']['email'];
    }
}
