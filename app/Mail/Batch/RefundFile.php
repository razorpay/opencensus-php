<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class RefundFile extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $emails = $data['emails'];

        $subject = $this->getSubject();

        return $this->view('emails.message')
                    ->with($data)
                    ->to($emails)
                    ->from('refunds@razorpay.com', 'Refunds File')
                    ->subject($subject)
                    ->attach($this->data['refundFile'])
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::BATCH_REFUNDS_FILE);
                    });
    }

    protected function getSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = 'Razorpay | Processed Refunds file for  ' . $today;

        return $subject;
    }
}
