<?php

namespace RZP\Mail;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;
use RZP\Mail\Base\Common;
use RZP\Models\Merchant;
use RZP\Models\BankAccount;

class AccountChange extends Mailable
{
    protected $bankAccount;

    protected $merchant;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(BankAccount\Entity $bankAccount, Merchant\Entity $merchant)
    {
        parent::__construct();

        $this->bankAccount = $bankAccount;

        $this->merchant = $merchant;
    }

    protected function addRecipients()
    {
        $this->to($this->merchant->getEmail(), $this->merchant->getName());
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
        $data = array_merge($this->merchant->toArray(), $this->newBankAccount->toArray());

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
