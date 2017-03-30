<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;

class FailedToAuthorized extends Base
{
    protected function getFrom()
    {
        if ($this->isMerchantEmail === false)
        {
            return $this->getCompleteEmail('care');
        }

        return parent::getFrom();
    }

    protected function addHtmlView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->view('emails.payment.failed_to_authorized');
        }

        $this->view('emails.payment.customer');

        return $this;
    }

    protected function addTextView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->text('emails.payment.failed_to_authorized_text');
        }

        $this->text('emails.payment.customer_text');

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FAILED_TO_AUTHORIZED;
    }

    public function isCustomerReceiptEmail()
    {
        if ($this->isMerchantEmail === false)
        {
            return true;
        }

        return false;
    }
}
