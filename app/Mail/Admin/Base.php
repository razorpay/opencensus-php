<?php

namespace RZP\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Base extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $admin;

    protected $org;

    protected $input;

    protected $header;

    public function __construct($admin, array $input)
    {
        $this->admin = $admin;

        $this->org = $admin->org;

        $this->input = $input;
    }

    public function canSend()
    {
        return true;
    }

    public function build()
    {
        $from       ='support@razorpay.com';
        $replyTo    = 'support@razorpay.com';
        $fromHeader = 'Team Razorpay';
        $to         = $this->admin->getEmail();
        $subject    = $this->getSubject();
        $view       = $this->getView();

        $data = $this->getData();

        $this->from($from, $fromHeader)
                ->to($to)
                ->replyTo($replyTo)
                ->subject($subject)
                ->with($data)
                ->withSwiftMessage(function ($message)
                {
                    $headers = $message->getHeaders();
                    $headers->addTextHeader(MailTags::HEADER, $this->header);
                });

        if (is_array($view) === true)
        {
            $this->view($view[0]);
            $this->text($view[1]);
        }
        else
        {
            $this->view($view);
        }

        return $this;
    }
}
