<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\MailTags;

class KotakSettlement extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Settlement';
    }

    protected function getSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Kotak Settlement files for $today";

        return $subject;
    }

    protected function getView()
    {
        return 'emails.admin.settlement';
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }

    protected function attachFile()
    {
        $file = $this->data['file'];

        $this->attach($file . '.xlsx')
                ->attach($file . '.txt');

        return $this;
    }
}
