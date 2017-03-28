<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class BankAccountChange extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $view;

    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $view, array $data)
    {
        $this->view = $view;

        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = 'Razorpay | Bank account change successful for ' . $this->data['label'];

        return $this->to($this->data['email'], $this->data['name'])
                    ->subject($subject)
                    ->view($this->view)
                    ->with($this->data)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::ACCOUNT_CHANGED);
                    });
    }
}
