<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;

class Refunded extends Base
{
    protected function addSender()
    {
        if ($this->isMerchantEmail === false)
        {
            $email = Common::MAIL_ADDRESSES[Common::CARE];

            $this->from($email);

            return $this;
        }

        return parent::addSender();
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
