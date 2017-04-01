<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;

class KotakPayout extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Payouts';
    }

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Kotak IMPS payouts files for $today";

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_PAYOUT_SUMMARY;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.payout');

        return $this;
    }
}
