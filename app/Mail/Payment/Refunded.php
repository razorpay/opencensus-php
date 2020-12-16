<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class Refunded extends Base
{
    protected function addHtmlView()
    {
        if($this->isCustomerReceiptEmail() === true)
        {
            $emailView = 'emails.mjml.customer.refund';
        } else
        {
            $emailView = 'emails.refund.common';
        }

        $this->view($emailView);
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
        return Constants::MAIL_ADDRESSES[Constants::NOREPLY];
    }

    protected function addMailData()
    {
        parent::addMailData();

        if ($this->isMerchantEmail() === true)
        {
            $this->data['type'] = 'merchant_transaction';
        }
        else
        {
            $this->data['type'] = 'customer';
        }

        $this->with($this->data);

        return $this;
    }

    protected function getSenderHeader(): string
    {
        return ($this->isMerchantEmail() === true) ?
            Constants::HEADERS[Constants::NOREPLY] :
            Constants::HEADERS[Constants::REPORTS];
    }
}
