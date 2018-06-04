<?php

namespace RZP\Mail\PaymentLink;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class Notify extends Mailable
{
    protected $data;
    protected $toEmail;
    protected $toName;

    public function __construct(array $data, string $toEmail)
    {
        parent::__construct();

        $this->data    = $data;
        $this->toEmail = $toEmail;
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.paymentlink.notify');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $fromName = Constants::HEADERS[Constants::NOREPLY];

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
            'payment_link' => $this->data
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYMENT_LINK_NOTIFY);
        });

        return $this;
    }
}
