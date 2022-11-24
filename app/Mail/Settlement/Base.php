<?php

namespace RZP\Mail\Settlement;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class Base extends Mailable
{
    protected $data;

    protected $channel;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $email =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $this->to($email);

        return $this;
    }

    protected function addSender()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $header = $this->getFromHeader();

        $this->from($email, $header);

        return $this;
    }

    protected function addCc()
    {
        $settlementsEmail =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $this->cc($settlementsEmail);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->getMailTag());
        });

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }
}
