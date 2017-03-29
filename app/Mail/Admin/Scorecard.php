<?php

namespace RZP\Mail\Admin;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Scorecard extends Mailable
{
    use Queueable, SerializesModels;

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $emails = ['scorecard@razorpay.com'];

        $date = Carbon::yesterday('Asia/Kolkata')->format('d-m-y');

        $subject = 'Razorpay | Scorecard for ' . $date;

        return $this->view('emails.message')
                    ->with($this->data)
                    ->from('scorecard@razorpay.com', 'Razorpay Scorecard')
                    ->to($emails)
                    ->subject($subject)
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::SCORECARD);
                    });
    }
}
