<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Refunded extends Base
{
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

    public function isCustomerReceiptEmail()
    {
        if ($this->isMerchantEmail === true)
        {
            return false;
        }

        return true;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($email);

        return $this;
    }
  
    protected function getSenderEmail(): string
    {
        return ($this->isMerchantEmail() === true) ?
            Constants::MAIL_ADDRESSES[Constants::NOREPLY] :
            Constants::MAIL_ADDRESSES[Constants::REPORTS];
    }

    protected function getSenderHeader(): string
    {
        return ($this->isMerchantEmail() === true) ?
            Constants::HEADERS[Constants::NOREPLY] :
            Constants::HEADERS[Constants::REPORTS];
    }
}
