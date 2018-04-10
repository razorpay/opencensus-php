<?php

namespace RZP\Mail\Merchant;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class AccountChangeRequest extends Mailable
{
    protected $bankAccount;

    protected $merchant;

    protected $recipientEmails;

    public function __construct(array $bankAccount, array $merchant, array $emails)
    {
        parent::__construct();

        $this->bankAccount = $bankAccount;

        $this->merchant = $merchant;

        $this->recipientEmails = [$this->merchant['email']];

        if (empty($emails) === false)
        {
            $this->recipientEmails = array_merge($this->recipientEmails, $emails);
        }
    }

    protected function addRecipients()
    {
        $name = $this->merchant['name'];

        $this->to($this->recipientEmails, $name);

        $this->cc(Constants::MAIL_ADDRESSES[Constants::SUPPORT], Constants::HEADERS[Constants::SUPPORT]);

        return $this;
    }

    protected function addSubject()
    {
        $label = $this->merchant['billing_label'] ?? $this->merchant['name'];

        $subject = 'Razorpay | Bank account change request for ' . $label;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = array_merge($this->merchant, $this->bankAccount);

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.bankaccount_change_request');

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_CHANGE_REQUEST);
        });

        return $this;
    }
}
