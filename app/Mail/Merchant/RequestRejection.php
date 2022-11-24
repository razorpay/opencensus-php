<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class RequestRejection extends Mailable
{
    protected $data;

    public function __construct(array $data)
    {
        parent::__construct();

        $this->data = $data;
    }

    protected function addRecipients()
    {
        $toEmail = $this->data['contact_email'];

        $toName = $this->data['contact_name'];

        $this->to($toEmail, $toName);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::SUPPORT];

        $fromName = Constants::HEADERS[Constants::SUPPORT];

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.request_rejection');

        return $this;
    }

    protected function addTextView()
    {
        $this->text('emails.merchant.request_rejection_text');

        return $this;
    }

    protected function addSubject()
    {

        $subject = 'Update on your application for '. $this->data['feature'] . ' - ' . $this->data['contact_name'] .
            ' | ' . $this->data['merchant_id'];

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::MERCHANT_REQUEST_REJECTED;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_REQUEST_REJECTED);
        });

        return $this;
    }
}
