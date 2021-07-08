<?php

namespace RZP\Mail\Payment;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class CustomerFailed extends Base
{

    const ZERODHA_MIDS = [
        'EQ8AzfZip2meDu',
        '4jrfbTLsua1pWJ',
        '9VIi8FakOk1SiV',
        '5zJACbxPORFLk8',
        'F02iHSCplfL5m7',
        'Eh54Q1B6HQKbS3',
        'E2Mw08X9tYN35Z',
        '5XBrWzODBkDPmi',
        '4sR6aB3rYxH3Sv',
    ];

    protected function addSubject()
    {
        $label = $this->data['merchant']['billing_label'] ?? $this->data['payment']['amount'];

        $subject = "Payment failed for $label";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.customer.failure');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.payment.merchant_text');

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::PAYMENT_FAILED;
    }

    public function isCustomerReceiptEmail()
    {
        return true;
    }

    protected function addReplyTo()
    {
        $email = $this->getSupportEmailInReplyTo();

        $this->replyTo($email);

        return $this;
    }

    protected function addMailData()
    {
       $data = $this->data;

       if ((isset($data['merchant']['id']) === true) and
           (isset($data['merchant']['support_details']) === true) and
           (isset($data['merchant']['support_details']['email']) === true) and
           (in_array($data['merchant']['id'], self::ZERODHA_MIDS) === true))
       {
           $data['merchant']['support_details']['email'] = 'support.zerodha.com';
       }

        $this->with($data);

        return $this;
    }
}
