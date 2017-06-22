<?php

namespace RZP\Mail\Report;

use Carbon\Carbon;
use RZP\Mail\Base\Common;
use RZP\Constants\MailTags;
use RZP\Mail\Base\Mailable;

class DSPReport extends Mailable
{
    protected $email;

    protected $outputFileLocalPath;

    public function __construct(string $email, string $outputFileLocalPath)
    {
        parent::__construct();

        $this->email = $email;

        $this->outputFileLocalPath = $outputFileLocalPath;
    }

    protected function addRecipients()
    {
        $this->to($this->email);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = 'Razorpay | Report for ' .$today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $data = ['body' => 'Report for ' .$today];

        $this->with($data);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.message');

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->outputFileLocalPath);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::DAILY_REPORT);
        });

        return $this;
    }
}
