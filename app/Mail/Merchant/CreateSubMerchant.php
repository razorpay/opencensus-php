<?php

namespace RZP\Mail\Merchant;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class CreateSubMerchant extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->view('emails.merchant.welcome')
                ->subject('Welcome to Razorpay')
                ->with($this->data)
                ->to($this->data['email'], $this->data['name'])
                ->withSwiftMessage(function ($message)
                {
                    $headers = $message->getHeaders();

                    $headers->addTextHeader(MailTags::HEADER, MailTags::WELCOME);
                });

        if (isset($this->data['cc_email']) === true)
        {
            $this->cc($this->data['cc_email']);
        }

        return $this;
    }
}
