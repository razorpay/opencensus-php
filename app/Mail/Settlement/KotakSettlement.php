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

    protected function addSubject()
    {
        $today = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $subject = "Kotak Settlement files for $today";

        $this->subject($subject);

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.admin.settlement');

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }

    protected function addAttachments()
    {
        $file = $this->data['file'];

        $this->attach($file . '.xlsx')
                ->attach($file . '.txt');

        return $this;
    }
}
