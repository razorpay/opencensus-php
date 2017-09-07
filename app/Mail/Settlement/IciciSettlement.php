<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Constants\MailTags;

class IciciSettlement extends Base
{
    protected function getFromHeader()
    {
        return 'ICICI Transfer File';
    }

    protected function addSubject()
    {
        $today = Carbon::today(Timezone::IST)->format('d-m-Y');

        $subject = "Icici Transfer files for $today";

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::ICICI_SETTLEMENT_FILES;
    }
}
