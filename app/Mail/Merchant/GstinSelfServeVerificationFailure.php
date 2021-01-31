<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class GstinSelfServeVerificationFailure extends Mailable
{
    protected $merchant;

    protected $merchantDetail;

    protected $recipientEmails;

    public function __construct(array $merchant, array $merchantDetail)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->merchantDetail = $merchantDetail;

        $this->recipientEmails = [$this->merchant['email']];
    }

    protected function addRecipients()
    {
        $name = $this->merchant['name'];

        $this->to($this->recipientEmails, $name);

        return $this;
    }

    protected function addSubject()
    {
        $label = $this->merchant['billing_label'] ?? $this->merchant['name'];

        $subject = 'Razorpay | Update on GSTIN change request for ' . $label;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        parent::addMailData();

        $this->with(array_merge($this->data, $this->merchant, $this->merchantDetail));

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.gstin_self_serve_verification_failure');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_CHANGED);
        });

        return $this;
    }
}
