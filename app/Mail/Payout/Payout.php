<?php

namespace RZP\Mail\Payout;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Payout extends Mailable
{
    protected $data;

    public function __construct(array $data, array $recipients)
    {
        parent::__construct();

        $this->data = $data;

        $this->recipients = $recipients;
    }

    protected function addRecipients()
    {
       $this->to($this->recipients);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addSubject()
    {
        $this->subject('Settlement Successfully Processed');

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
            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYOUT_SUCCESSFUL);
        });

        return $this;
    }
}
