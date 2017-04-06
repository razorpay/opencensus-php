<?php

namespace RZP\Mail\Banking;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;

class AccountChange extends Mailable
{
    protected $bankAccount;

    protected $merchant;

    public function __construct(BankAccount\Entity $bankAccount, Merchant\Entity $merchant)
    {
        $this->bankAccount = $bankAccount;

        $this->merchant = $merchant;
    }

    protected function addRecipients()
    {
        $email = $this->merchant->getEmail();
        $name = $this->merchant->getName();

        $this->to($email, $name);

        return $this;
    }

    protected function addSubject()
    {
        $label = $this->merchant->getBillingLabelElseName();

        $subject = 'Razorpay | Bank account change successful for ' . $label;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = array_merge($this->merchant->toArray(), $this->bankAccount->toArray());

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
