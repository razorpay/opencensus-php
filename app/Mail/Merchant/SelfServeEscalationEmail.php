<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class SelfServeEscalationEmail extends Mailable
{
    protected $data;

    public function __construct(string $subject, array $data)
    {
        parent::__construct();

        $this->data = $data;
        $this->subject  = $subject;
    }

    protected function addRecipients()
    {
        $this->to($this->data['recipients']);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.self_serve_escalation');

        return $this;
    }

    protected function addSender()
    {
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $senderName = Constants::HEADERS[Constants::NOREPLY];

        $this->from($senderEmail, $senderName);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }
}
