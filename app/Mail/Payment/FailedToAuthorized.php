<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Common;

class FailedToAuthorized extends Base
{
    protected function addSender()
    {
        if ($this->isMerchantEmail === false)
        {
            $email = Common::MAIL_ADDRESSES[Common::CARE];

            $header = Common::HEADERS[Common::CARE];

            $this->from($email);

            return $this;
        }

        return parent::addSender();
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
        if ($this->isMerchantEmail === true)
        {
            return false;
        }

        return true;
    }
}
