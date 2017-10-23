<?php

namespace RZP\Mail\Gateway\EMandate;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

/** Implement Recipients, and Subject in your class */

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
        $email = Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

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
        if (isset($this->data['file_data']) === true)
        {
            $this->attach($this->data['file_data']['signed_url'], ['as' => $this->data['file_data']['file_name']]);
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
}
