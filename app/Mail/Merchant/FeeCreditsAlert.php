<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class FeeCreditsAlert extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $this->to($this->data['email']);
        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ALERTS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SUPPORT];
        $header = Constants::HEADERS[Constants::SUPPORT];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addCc()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::CREDITS_ALERTS];

        $this->cc($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = '[Alert] Razorpay | Low Fee Credits Balance for '.$this->data['merchant_dba'];

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->data['merchant_id']);

            $headers->addTextHeader(MailTags::HEADER, MailTags::FEE_CREDITS_ALERT);
        });

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.fee_credits_alert');

        return $this;
    }
}
