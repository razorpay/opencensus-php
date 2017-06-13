<?php

namespace RZP\Mail\Batch;

use Carbon\Carbon;

use RZP\Mail\Base\Constants;

class Refund extends Base
{
    protected function addSender()
    {
        $fromEmail  = Constants::MAIL_ADDRESSES[Constants::REFUNDS];
        $fromHeader = Constants::HEADERS[Constants::REFUNDS];

        $this->from($fromEmail, $fromHeader);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Razorpay | Processed Refunds file for  $today";

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
}
