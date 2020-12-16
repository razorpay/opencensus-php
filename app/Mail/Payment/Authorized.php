<?php

namespace RZP\Mail\Payment;

use RZP\Mail\Base\Constants;

class Authorized extends Base
{
    protected function addHtmlView()
    {
        //$emailView = $this->getView('emails.mjml.customer.payment', 'emails.payment.customer');

        $emailView = 'emails.mjml.customer.payment';

        $this->view($emailView);

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.customer_text');

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $this->data['data'] = $this->getCustomerSupportText();

        return $this;
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

    protected function getSenderEmail(): string
    {
        return Constants::MAIL_ADDRESSES[Constants::NOREPLY];
    }

    protected function getSenderHeader(): string
    {
        return Constants::HEADERS[Constants::NOREPLY];
    }

    protected function getCustomCustomerReplyToEmail(): string
    {
        $merchantId = $this->data['merchant']['id'];

        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        // Zebpay
        if ($merchantId === '8iMbVsEnv1HCo0')
        {
            $email = 'support@zebpay.com';
        }
        // Koinex
        else if ($merchantId === '8Gx5vN29m83OUY')
        {
            $email = 'team@koinex.in';
        }

        return $email;
    }
}
