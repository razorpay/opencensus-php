<?php

namespace RZP\Mail\Report;

use Carbon\Carbon;
use Symfony\Component\Mime\Email;

use RZP\Mail\Base\Common;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class DSPReport extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $emails = explode(',', $this->data['emails']);

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function addMailData()
    {
        $body = ['body' => $this->data['body']];

        $this->with($body);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['signed_url'], [
            'as'   => $this->data['filename'],
            'mime' => 'text/csv'
        ]);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_REPORT);
        });

        return $this;
    }
}
