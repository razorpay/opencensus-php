<?php

namespace RZP\Mail\Payment;

use RZP\Mail\Base\Constants;

class Authorized extends Base
{
    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::CARE];

        $header = Constants::HEADERS[Constants::CARE];

        $this->from($email, $header);

        return $this;
    }

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
