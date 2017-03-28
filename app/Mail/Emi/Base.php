<?php

namespace RZP\Mail\Emi;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use RZP\Constants\MailTags;

class Base extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $emailIdsToSendTo;

    protected $bankName;

    protected $filePath;

    public function __construct(array $emailIdsToSendTo, string $bankName)
    {
        $this->emailIdsToSendTo = $emailIdsToSendTo;

        $this->bankName = $bankName;
    }

    public function build()
    {
        $to = $this->getToEmails();

        $fromHeader = $this->getFromHeader();

        $data = $this->getData();

        $this->from('emifiles@razorpay.com', $fromHeader)
                    ->to($to)
                    ->subject($data['subject'])
                    ->with($data)
                    ->view('emails.message')
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::EMI_FILE);
                    });

        if ($this->filePath !== null)
        {
            $this->attach($this->file);
        }

        return $this;
    }

    protected function getFromHeader()
    {
        return '';
    }

    protected function getData()
    {
        return [];
    }

    protected function getToEmails()
    {
        return $this->emailIdsToSendTo;
    }
}
