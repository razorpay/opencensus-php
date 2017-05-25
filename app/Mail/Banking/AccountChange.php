<?php

namespace RZP\Mail\Banking;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;

class AccountChange extends Mailable
{
    protected $bankAccount;

    protected $merchant;

    public function __construct(array $bankAccount, array $merchant)
    {
        $this->bankAccount = $bankAccount;

        $this->merchant = $merchant;
    }

    protected function addRecipients()
    {
        $email = $this->merchant['email'];
        $name = $this->merchant['name'];

        $this->to($email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $label = $this->merchant['billing_label'];

        if (empty($label) === true)
        {
            $label = $this->merchant['name'];
        }

        $subject = 'Razorpay | Bank account change successful for ' . $label;

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
        $this->view('emails.merchant.bankaccount_change');

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
