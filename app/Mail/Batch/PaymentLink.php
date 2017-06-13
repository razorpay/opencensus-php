<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Mail\Base\Constants;

class PaymentLink extends Base
{
    protected function addSender()
    {
        $fromEmail  = Constants::MAIL_ADDRESSES[Constants::INVOICES];
        $fromHeader = Constants::HEADERS[Constants::PAYMENT_LINK];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Razorpay | Processed payment link file for $today";

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $data = [
            'body' => 'Please find attached processed payment link file',
        ];

        $this->with($data);

        return $this;
    }
}
