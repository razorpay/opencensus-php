<?php

namespace RZP\Mail\Payment;

use RZP\Mail\Base\Constants;

class Authorized extends Base
{
    protected function addHtmlView()
    {
        $this->view('emails.payment.customer');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.customer_text');

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
