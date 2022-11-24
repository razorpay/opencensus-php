<?php

namespace RZP\Mail\OAuth;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class AppAuthorized extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
       $this->to($this->data['merchant']['email'], $this->data['user']['name']);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.oauth.app_authorization');

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Razorpay | Application access grant notification');

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
            $headers->addTextHeader(MailTags::HEADER, MailTags::OAUTH_APP_AUTHORIZED);
        });

        return $this;
    }
}
