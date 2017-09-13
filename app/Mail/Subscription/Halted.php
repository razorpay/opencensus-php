<?php

namespace RZP\Mail\Subscription;

class Halted extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscriptions.halted');

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
