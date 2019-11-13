<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class EsEnabledNotify extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = ['ayush.bansal@razorpay.com', 'abhirup.bhabani@razorpay.com', 'anubhav.jain@razorpay.com'];

        $toName = ['Ayush Bansal', 'Abhirup Bhabhani', 'Anubhav Jain'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::CAPITAL_SUPPORT];

        $fromName = Constants::HEADERS[Constants::CAPITAL_SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.es_enabled_notify');

        return $this;
    }

    protected function addSubject()
    {
        $subject = 'A new Merchant has joined ES scheduled!!';

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::FEATURE_ENABLED);
        });

        return $this;
    }
}
