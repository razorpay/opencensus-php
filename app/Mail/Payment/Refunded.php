<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;

class Refunded extends Base
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
        $this->view('emails.refund.common');

        return $this;
    }

    protected function getAction()
    {
        return 'Refund';
    }

    protected function getMailTag()
    {
        return MailTags::REFUND_SUCCESSFUL;
    }

    public function isCustomerReceiptEmailRequired()
    {
        if ($this->isMerchantEmail === false)
        {
            return true;
        }

        return false;
    }
}
