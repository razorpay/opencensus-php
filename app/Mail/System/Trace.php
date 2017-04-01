<?php

namespace RZP\Mail\System;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class Trace extends Mailable
{
    const CHANNEL = "Razorpay API";

    protected $msg;

    protected $mode;

    public function __construct(string $msg, string $mode)
    {
        $this->msg = $msg;

        $this->mode = $mode;
    }

    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::ERRORS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Common::MAIL_ADDRESSES[Common::DEVELOPERS];

        $this->replyTo($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = self::CHANNEL . '-' . $this->mode . ' - Critical error occurred';

        $this->subject($subject);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::CRITICAL_ERROR);
        });

        return $this;
    }
}
