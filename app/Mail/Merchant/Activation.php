<?php

namespace RZP\Mail\Merchant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Activation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $merchant;

    protected $data;

    public function __construct(Merchant\Entity $merchant, array $data)
    {
        $this->merchant = $merchant;

        $this->data = $data;
    }

    public function build()
    {
        $to = $this->getTo();

        $subject = $this->getSubject();

        return $this->view('emails.merchant.activation')
                    ->text('emails.merchant.activation_text')
                    ->to($to)
                    ->cc('notifications@razorpay.com')
                    ->subject($subject)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();
                        $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_ACTIVATED);
                    });
    }

    protected function getSubject()
    {
        $subjectName = $merchant->getBillingLabelElseName();

        $subject = "Razorpay | Account activated for $subjectName";

        return $subject;
    }

    protected function getTo()
    {
        // For marketplace accounts, send this email to the parent merchant
        if ($this->merchant->isLinkedAccount() === true)
        {
            return $merchant->parent->getEmail();
        }

        return $merchant->getEmail();
    }
}
