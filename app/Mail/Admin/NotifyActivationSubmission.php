<?php

namespace RZP\Mail\Admin;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

class NotifyActivationSubmission extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        parent::addMailData();

        $this->data = array_merge($this->data, $data);
    }

    protected function addRecipients()
    {
        $toEmail = Constants::MAIL_ADDRESSES[Constants::ACTIVATION];

        $toName = Constants::HEADERS[Constants::ACTIVATION];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.notify_activation_submission');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $subject = "New activation form submitted for " . $this->data['business_name'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $mailData = [
            'merchant_details' => $this->data
        ];

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::NOTIFY_ACTIVATION_SUBMISSION);
        });

        return $this;
    }
}
