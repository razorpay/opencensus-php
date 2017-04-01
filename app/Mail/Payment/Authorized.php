<?php

namespace RZP\Mail\Payment;

class Authorized extends Base
{
    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::CARE];

        $this->from($email);

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
