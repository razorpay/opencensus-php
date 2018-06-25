<?php

namespace RZP\Mail\PaymentLink;

use RZP\Constants\Entity;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

/**
 * Payment request mail for given payment link to be sent to customer/client
 */
class PaymentRequest extends Mailable
{
    protected $paymentLink;
    protected $toEmail;

    public function __construct(array $paymentLink, string $toEmail)
    {
        parent::__construct();

        $this->paymentLink = $paymentLink;
        $this->toEmail     = $toEmail;
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment_link.payment_request');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $fromName  = Constants::HEADERS[Constants::NOREPLY];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $subject = "Payment link notification";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            Entity::PAYMENT_LINK => $this->paymentLink,
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYMENT_LINK_PAYMENT_REQUEST);
        });

        return $this;
    }
}
