<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class RefundArnUpdated extends Base
{
    protected function addHtmlView()
    {
        if($this->isCustomerReceiptEmail() === true)
        {
            $emailView = 'emails.mjml.customer.refund_arn_updated';
        }
        else
        {
            $emailView = 'emails.refund.arn_updated';
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
        return MailTags::REFUND_ARN_UPDATE;
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

    protected function addSubject()
    {
        $subject = 'ARN update for refund tracking';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
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
