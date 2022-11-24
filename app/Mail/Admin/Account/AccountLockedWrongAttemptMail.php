<?php

namespace RZP\Mail\Admin\Account;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class AccountLockedWrongAttemptMail extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $recipientEmail = $this->data['admin']['email'];

        $recipientName = $this->data['admin']['name'];

        $this->to($recipientEmail, $recipientName);

        return $this;
    }

    protected function addReplyTo()
    {
        $email = Constants::MAIL_ADDRESSES[Constants::NOREPLY];

        $this->replyTo($email);

        return $this;
    }

    protected function addSender()
    {
        $senderEmail = Constants::MAIL_ADDRESSES[Constants::NOREPLY];
        $senderName = Constants::HEADERS[Constants::NOREPLY];

        $this->from($senderEmail, $senderName);

        return $this;
    }


    protected function addSubject()
    {
        $this->subject('Your Account is Locked');

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.mjml.admin.2FA.account_locked');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message) {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::ADMIN_ACCOUNT_LOCKED);
        });

        return $this;
    }

}
