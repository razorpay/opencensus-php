<?php

namespace RZP\Mail\Subscription;

class Activated extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscriptions.customer');

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
