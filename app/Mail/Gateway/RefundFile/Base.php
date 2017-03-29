<?php

namespace RZP\Mail\Gateway;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Base extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $type;

    protected $data;

    public function __construct(array $data, string $type)
    {
        $this->data = $data;

        $this->type = $type;
    }

    public function build()
    {
        $to = Metadata::RECIPIENT_EMAILS_MAP[$this->type];

        $subject = $this->getSubject();

        $fromHeader = Metadata::FROM_HEADER_MAP;

        $mailTagHeader = Metadata::MAILTAG_MAP[$this->type];

        return $this->view('emails.message')
                    ->from('refunds@razorpay.com', $fromHeader)
                    ->to($to)
                    ->subject($subject)
                    ->with($this->data)
                    ->attach($this->data['file_path'])
                    ->withSwiftMessage(function ($message) use ($mailTagHeader)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, $mailTagHeader);
                    });
    }

    protected function getSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = Metadata::SUBJECT_MAP[$this->type] . $today;

        return $subject;
    }
}
