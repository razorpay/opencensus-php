<?php

namespace RZP\Mail\Settlement;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Base extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    protected $fromHeader;

    protected $subject;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $emails = ['settlements@razorpay.com'];

        $fromHeader = $this->getFromHeader();

        $subject = $this->getSubject();

        $view = $this->getView();

        $this->view($view)
                ->from('settlements@razorpay.com', $fromHeader)
                ->to($emails)
                ->subject($subject)
                ->with($this->data)
                ->attachFile()
                ->setHeaders();

        return $this;
    }

    protected function getFromHeader()
    {
        ;
    }

    protected function getSubject()
    {
        ;
    }

    protected function getView()
    {
        return 'emails.message';
    }

    protected function getMailTag()
    {
        ;
    }

    protected function attachFile()
    {
        $this->attach($this->data['file']);

        return $this;
    }

    protected function setHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $this->getMailTag());
        });

        return $this;
    }
}
