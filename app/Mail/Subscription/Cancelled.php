<?php

namespace RZP\Mail\Subscription;

class Cancelled extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscriptions.cancelled');

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
