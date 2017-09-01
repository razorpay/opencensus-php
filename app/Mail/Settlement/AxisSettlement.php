<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;

use RZP\Constants\MailTags;

class AxisSettlement extends Base
{
    protected function getFromHeader()
    {
        return 'Axis Transfer File';
    }

    protected function addRecipients()
    {
        $recipients = [
            'paymandate.cms@axisbank.com',
            'bhupendra.kambli@axisbank.com',
            'ganesh.kotian@axisbank.com',
            'lavania.peter@axisbank.com',
            'prasad.shinde@axisbank.com',
            'anushree.mahapadi@axisbank.com',
            'sona.hindalekar@axisbank.com',
            ];

        $this->to($recipients);

        return $this;
    }

    protected function addCc()
    {
        $settlementsEmail =  Constants::MAIL_ADDRESSES[Constants::SETTLEMENTS];

        $this->cc($settlementsEmail);

        return $this;
    }

    protected function addMailData()
    {
        $this->data['body'] = 'Kindly approve the attached transaction details.';

        $this->with($this->data);

        return $this;
    }

    protected function addSubject()
    {
        $today = Carbon::today(Timezone::IST)->format('d-m-Y');

        $subject = "Axis Transfer File for $today";

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::AXIS_SETTLEMENT_FILES;
    }
}
