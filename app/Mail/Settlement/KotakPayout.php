<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;

class KotakPayout extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Payouts';
    }

    protected function getSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Kotak IMPS payouts files for $today";

        return $subject;
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_PAYOUT_SUMMARY;
    }

    protected function getView()
    {
        return 'emails.admin.payout';
    }
}
