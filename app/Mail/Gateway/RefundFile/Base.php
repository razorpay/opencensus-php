<?php

namespace RZP\Mail\Gateway;

use Carbon\Carbon;
use RZP\Mail\base \Mailable;
use RZP\Mail\base \Common;

class Base extends Mailable
{
    protected $type;

    protected $data;

    public function __construct(array $data, string $type)
    {
        $this->data = $data;

        $this->type = $type;
    }

    protected function addSender()
    {
        $fromEmail = Common::MAIL_ADDRESSES[Common::REFUNDS];

        $fromHeader = Metadata::FROM_HEADER_MAP[$this->type];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = Metadata::RECIPIENT_EMAILS_MAP[$this->type];

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = Metadata::SUBJECT_MAP[$this->type] . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['file_path']);

        return $this;
    }

    protected function addHeaders()
    {
        $header = Metadata::MAILTAG_MAP[$this->type];

        $this->withSwiftMessage(function ($message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }
}
