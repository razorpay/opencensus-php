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

        $this->from('settlements@razorpay.com', $this->fromHeader)
                ->to($emails)
                ->subject($this->subject)
                ->with($this->data);

        return $this;
    }
}
