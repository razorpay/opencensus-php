<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class Base extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function addRecipients()
    {
        $email =  Common::MAIL_ADDRESSES[Common::SETTLEMENTS];

        $this->to($email);

        return $this;
    }

    protected function addSender()
    {
        $email = Common::MAIL_ADDRESSES[Common::SETTLEMENTS];

        $header = $this->getFromHeader();

        $this->from($email, $header);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addAttachments()
    {
        if (isset($this->data['file']) === true)
        {
            $this->attach($this->data['file']);
        }

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

            $headers->addTextHeader(MailTags::HEADER, $this->getMailTag());
        });

        return $this;
    }

    protected function getMailTag()
    {
        ;
    }
}
