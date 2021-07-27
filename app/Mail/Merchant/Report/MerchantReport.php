<?php

namespace RZP\Mail\Merchant\Report;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class MerchantReport extends Mailable
{
    protected $data;

    protected $template;

    protected $recipients;

    public function __construct(string $template, string $subject, array $recipients, array $data)
    {
        parent::__construct();

        $this->data = $data;
        $this->subject  = $subject;
        $this->template = $template;
        $this->recipients = $recipients;
    }

    protected function addRecipients()
    {
        $this->to($this->recipients);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->template);

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
