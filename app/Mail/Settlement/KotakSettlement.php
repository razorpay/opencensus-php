<?php

namespace RZP\Mail\Settlement;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Constants\MailTags;

class KotakSettlement extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Settlement';
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
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }

    protected function addAttachments()
    {
        $this->attach($this->data['excelFile']);
        $this->attach($this->data['textFile']);

        return $this;
    }

    protected function getSubject()
    {
        $today = Carbon::now(Timezone::IST)->format('d-m-Y');

        $subject = "Kotak Settlement files for $today";

        return $subject;
    }
}
