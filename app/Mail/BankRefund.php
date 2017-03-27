<?php

namespace RZP\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BankRefund extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $view = 'emails.admin.' . lcfirst($this->data['bankName']) . '_refunds';

        $this->from('settlements@razorpay.com', $this->data['bankName'] . ' Netbanking Refunds')
                ->subject($this->data['subject'])
                ->to('settlements@razorpay.com')
                ->with($this->data)
                ->view($view);

        if (empty($this->data['claimsFile']) === false)
        {
            $this->attach($this->data['claimsFile']);
        }

        if (empty($this->data['refundFile']) === false)
        {
            $this->attach($this->data['refundsFile']);
        }

        return $this;
    }
}
