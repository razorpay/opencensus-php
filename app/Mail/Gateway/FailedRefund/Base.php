<?php

namespace RZP\Mail\Gateway\FailedRefund;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;
use RZP\Models\Payment\Gateway;
use RZP\Mail\Gateway\RefundFile;

class Base extends RefundFile\Base
{
    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = Constants::SUBJECT_MAP[$this->type] . $today;

        return $subject;
    }

    protected function addMailData()
    {
        $mailData = [
            'body' => Constants::BODY_MAP[$this->type],
        ];

        $mailData = array_merge($mailData, $this->data);

        $this->with($mailData);

        return $this;
    }

    protected function addHeaders()
    {
        $header = Constants::MAILTAG_MAP[$this->type];

        $this->withSwiftMessage(function ($message) use ($header)
        {
            $headers = $message->getHeaders();

            $headers->addTextHeader(MailTags::HEADER, $header);
        });

        return $this;
    }
}
