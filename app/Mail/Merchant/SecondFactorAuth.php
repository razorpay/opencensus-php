<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Merchant;

class SecondFactorAuth extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['merchant']['email'];

        $toName = $this->data['merchant']['name'];

        $this->to($toEmail, $toName);

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
        $subject = '2FA %s for Razorpay Dashboard';

        $action = ($this->data['merchant'][Merchant\Entity::SECOND_FACTOR_AUTH] === true) ? "Enabled" : "Disabled";

        $this->subject(sprintf($subject, $action));

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.merchant.2fa_notification');

        return $this;
    }
}
