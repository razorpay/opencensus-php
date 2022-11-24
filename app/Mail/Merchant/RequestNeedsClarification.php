<?php

namespace RZP\Mail\Merchant;

use Symfony\Component\Mime\Email;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Constants;

class RequestNeedsClarification extends Mailable
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

        // CCing support to create a ticket on Zendesk
        $this->cc(Constants::MAIL_ADDRESSES[Constants::SUPPORT], Constants::HEADERS[Constants::SUPPORT]);

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
        $this->view('emails.merchant.request_needs_clarification');

        return $this;
    }

    protected function addSubject()
    {

        $subject = 'Clarifications needed on your application for '. $this->data['feature']
            . ' - ' . $this->data['contact_name'] . ' | ' . $this->data['merchant_id'];

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
        return MailTags::MERCHANT_REQUEST_NEEDS_CLARIFICATION;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::MERCHANT_REQUEST_NEEDS_CLARIFICATION);
        });

        return $this;
    }
}
