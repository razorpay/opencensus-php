<?php

namespace RZP\Mail\Payment\Fraud;

use Symfony\Component\Mime\Email;

use RZP\Mail\Payment\Base;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;

class DomainMismatch extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data, true);
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment.fraud.domain_mismatch');

        return $this;
    }

    protected function addSubject()
    {
        $subject = '[IMP] Payment failed due to  attempts from unregistered website for MID - ' . $this->data['merchant']['id'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $merchantId = $this->data['merchant']['id'];

            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $merchantId);

            $headers->addTextHeader(MailTags::HEADER, MailTags::FRAUD_NOTIFICATION_DOMAIN_MISMATCH);
        });

        return $this;
    }

    protected function addSender()
    {
        $email  = $this->getSenderEmail();

        $header = Constants::HEADERS[Constants::NOREPLY];

        $this->from($email, $header);

        return $this;
    }
}
