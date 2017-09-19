<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;

class KotakPayout extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Payouts';
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addMailData()
    {
        $subject =  $this->getSubject();

        $this->data['subject'] = $subject;

        $this->with($this->data);

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

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = "Kotak IMPS payouts files for $today";

        return $subject;
    }
}
