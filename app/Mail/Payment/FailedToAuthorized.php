<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class FailedToAuthorized extends Base
{
    protected function addHtmlView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->view('emails.payment.failed_to_authorized');
        }
        else
        {
            $this->view('emails.payment.customer');
        }

        return $this;
    }

    protected function addTextView()
    {
        if ($this->isMerchantEmail === true)
        {
            $this->text('emails.payment.failed_to_authorized_text');
        }
        else
        {
            $this->text('emails.payment.customer_text');
        }

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::FAILED_TO_AUTHORIZED;
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
        if ($this->isMerchantEmail === true)
        {
            return false;
        }

        return true;
    }
}
