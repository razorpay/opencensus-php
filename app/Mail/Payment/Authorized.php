<?php

namespace RZP\Mail\Payment;

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

    protected function addMailData()
    {
        $this->data['data'] = $this->getCustomerSupportText();

        return parent::addMailData();
    }

    protected function addReplyTo()
    {
        $email = $this->getCustomCustomerReplyToEmail();

        $this->replyTo($email);

        return $this;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }
}
