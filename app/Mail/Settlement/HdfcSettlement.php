<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;

class HdfcSettlement extends Base
{
    protected function getFromHeader()
    {
        return $this->data['channel'] . ' Settlement';
    }

    protected function addSubject()
    {
        $subject = $this->getSubject();

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.settlement');

        return $this;
    }

    protected function addMailData()
    {
        $subject = $this->getSubject();

        $this->data['subject'] = $subject;

        $this->with($this->data);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::HDFC_SETTLEMENT_FILES;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = $this->data['channel'] . " settlement files for $today";

        return $subject;
    }
}
