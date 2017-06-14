<?php

namespace RZP\Mail\Gateway\RefundFile;

use Carbon\Carbon;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class Base extends Mailable
{
    protected $type;

    protected $data;

    protected $email;

    protected $template;

    public function __construct(array $data,
                                string $type,
                                string $email = null,
                                string $template = 'emails.message')
    {
        parent::__construct();

        $this->data = $data;

        $this->type = $type;

        $this->email = $email;

        $this->template = $template;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::REFUNDS];

        $fromHeader = Constants::HEADER_MAP[$this->type];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addRecipients()
    {
        $emails = $this->email ?? Constants::RECIPIENT_EMAILS_MAP[$this->type];

        $this->to($emails);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = Constants::SUBJECT_MAP[$this->type] . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'body' => Constants::BODY_MAP[$this->type]
        ];

        $mailData = array_merge($mailData, $this->data);

        $this->with($mailData);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view($this->template);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['signed_url'], ['as' => $this->data['file_name']]);

        return $this;
    }

    protected function addHeaders()
    {
        $header = Constants::MAILTAG_MAP[$this->type];

        $this->withSwiftMessage(function ($message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }
}
