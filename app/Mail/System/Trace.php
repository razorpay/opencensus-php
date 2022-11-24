<?php

namespace RZP\Mail\System;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Trace extends Mailable
{
    const CHANNEL = "Razorpay API";

    protected $msg;

    protected $mode;

    public function __construct(string $msg, string $mode)
    {
        parent::__construct();

        $this->msg = $msg;

        $this->mode = $mode;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::ERRORS];

        $this->from($email);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::DEVELOPERS];

        $this->replyTo($email);

        return $this;
    }

    protected function addSubject()
    {
        $subject = self::CHANNEL . '-' . $this->mode . ' - Critical error occurred';

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addMailData()
    {
        $this->with([
            'msg' => $msg
        ]);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();
            $headers->addTextHeader(MailTags::HEADER, MailTags::CRITICAL_ERROR);
        });

        return $this;
    }
}
