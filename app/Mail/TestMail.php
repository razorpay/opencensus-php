<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('support@razorpay.com', 'Razorpay Support')
                    ->replyTo('care@razorpay.com', 'Razorpay Care')
                    ->subject('Test mail')
                    ->view('emails.testing')
                    ->with(['name' => 'Some name'])
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_CHANGED);
                    });
    }
}
