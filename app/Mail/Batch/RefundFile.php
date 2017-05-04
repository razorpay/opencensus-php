<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Constants\MailTags;
use RZP\Mail\Base\Constants;
use RZP\Mail\Base\Mailable;
use RZP\Models\Batch;

class RefundFile extends Mailable
{
    protected $merchant;

    protected $filePath;

    public function __construct(array $merchant, string $filePath)
    {
        parent::__construct();

        $this->merchant = $merchant;

        $this->filePath = $filePath;
    }

    protected function addRecipients()
    {
        $emails = $this->merchant['transaction_report_email'];

        $this->to($emails);

        return $this;
    }

    protected function addSender()
    {
        $fromEmail = Constants::MAIL_ADDRESSES[Constants::REFUNDS];
        $fromHeader = Constants::HEADERS[Constants::REFUNDS];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = 'Razorpay | Processed Refunds file for  ' . $today;

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'body' => 'Please find attached processed Refunds File',
        ];

        $this->with($data);

        return $this;
    }

    protected function addAttachments()
    {
        $this->attach($this->filePath);

        return $this;
    }

    protected function addHeaders()
    {
        $this->withSwiftMessage(function ($message)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, MailTags::BATCH_REFUNDS_FILE);
        });

        return $this;
    }
}
