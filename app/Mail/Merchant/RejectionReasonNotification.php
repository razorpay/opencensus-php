<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class RejectionReasonNotification extends Mailable
{
    protected $user;

    protected $messageBody;

    protected $messageSubject;

    public function __construct(array $user, string $messageSubject, string $messageBody)
    {
        parent::__construct();

        $this->user           = $user;

        $this->messageBody    = $messageBody;

        $this->messageSubject = $messageSubject;
    }

    protected function addRecipients()
    {
        $this->to($this->user['email'], $this->user['name']);

        return $this;
    }

    protected function addSubject()
    {
        $this->subject($this->messageSubject);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $data = [
            'messageBody'    => $this->messageBody,
        ];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.rejection_reason_notification');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::WEBSITE_SELF_SERVE_REJECTION_REASON);
        });

        return $this;
    }
}
