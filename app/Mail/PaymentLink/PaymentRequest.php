<?php

namespace RZP\Mail\PaymentLink;

use Symfony\Component\Mime\Email;

use RZP\Constants\Entity;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;

/**
 * Payment request mail for given payment link to be sent to customer/client
 */
class PaymentRequest extends Mailable
{
    /**
     * Payload contains payment_link, merchant & other few vars to be used in mail template
     * @var array
     */
    protected $mailPayload;

    /**
     * Recipient email address
     * @var string
     */
    protected $toEmail;

    public function __construct(array $mailPayload, string $toEmail)
    {
        parent::__construct();

        $this->mailPayload = $mailPayload;
        $this->toEmail     = $toEmail;
    }

    protected function getSenderEmail(): string
    {
        $orgCode = $this->mailPayload['org']['custom_code'] ?? '';

        return Constants::getSenderEmailForOrg($orgCode, Constants::NOREPLY);
    }

    protected function getSenderHeader(): string
    {
        $orgCode = $this->mailPayload['org']['custom_code'] ?? '';

        return Constants::getSenderNameForOrg($orgCode, Constants::NOREPLY);
    }

    protected function addRecipients()
    {
        $this->to($this->toEmail);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.payment_link.payment_request');

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = $this->getSenderEmail();
        $fromName  = $this->getSenderHeader();

        $this->from($fromEmail, $fromName);

        return $this;
    }

    protected function addSubject()
    {
        $title = $this->mailPayload['payment_link']['title'];
        $subject = "Payment Request: {$title}";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $this->with($this->mailPayload);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSymfonyMessage(function (Email $message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::PAYMENT_LINK_PAYMENT_REQUEST);
        });

        return $this;
    }
}
