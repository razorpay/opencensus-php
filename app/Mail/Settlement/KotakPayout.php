<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;

class KotakPayout extends Base
{
    public function __construct(array $data)
    {
        parent::__construct($data);

        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $this->subject = "Kotak IMPS payouts files for $today";

        $this->fromHeader = 'Kotak Payouts';
    }

    public function build()
    {
        parent::build();

        return $this->view('emails.admin.payout')
                    ->attach($this->data['file'])
                    ->withSwiftMessage(function ($message)
                    {
                        $headers = $message->getHeaders();

                        $headers->addTextHeader(MailTags::HEADER, MailTags::KOTAK_PAYOUT_SUMMARY);
                    });
    }
}
