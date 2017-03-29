<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Constants\MailTags;

class AccountChange extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $bankAccount;

    protected $merchant;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(BankAccount\Entity $bankAccount, Merchant\Entity $merchant)
    {
        $this->bankAccount = $bankAccount;

        $this->merchant = $merchant;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->getSubject();

        $data = array_merge($this->merchant->toArray(), $this->newBankAccount->toArray());

        return $this->to($this->merchant->getEmail(), $this->merchant->getName())
                    ->subject($subject)
                    ->view('emails.merchant.bankaccount_change')
                    ->with($data)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_CHANGED);
                    });
    }

    protected function getSubject()
    {
        $label = $this->merchant->getBillingLabelElseName();

        $subject = 'Razorpay | Bank account change successful for ' . $label;

        return $subject;
    }
}
