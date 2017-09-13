<?php

namespace RZP\Mail\Subscription;

class Charged extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscriptions.charged');

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
