<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Trace extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    const CHANNEL = "Razorpay API";

    protected $msg;

    protected $mode;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $msg, string $mode)
    {
        $this->msg = $msg;

        $this->mode = $mode;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->getSubject();

        return $this->view('email.message')
                    ->with($this->msg)
                    ->subject($subject)
                    ->from('errors@razorpay.com')
                    ->replyTo('developers@razorpay.com')
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();
                        $headers->addTextHeader(MailTags::HEADER, MailTags::CRITICAL_ERROR);
                    });
    }

    protected function getSubject()
    {
        return self::CHANNEL . '-' . $this->mode . ' - Critical error occurred';
    }
}
