<?php

namespace RZP\Mail\Merchant\ActivationSubmission;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Admin\Org;

class NotifyAdmin extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
       $this->to($this->data['to_email'], $this->data['to_name']);

       return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.notify_activation_submission');

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->data['subject']);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data['mail_data']);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::NOTIFY_ACTIVATION_SUBMISSION);
        });

        return $this;
    }
}
