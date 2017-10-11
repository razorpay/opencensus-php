<?php

namespace RZP\Mail\Dispute;

use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Base extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $fromHeader = Constants::HEADERS[Constants::DISPUTES];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $merchantEmail = $this->data['merchant']['email'];

        $merchantName = $this->data['merchant']['name'];

        $this->to($merchantEmail, $merchantName);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DISPUTES];

        $header = Constants::HEADERS[Constants::DISPUTES];

        $this->replyTo($email, $header);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }
}