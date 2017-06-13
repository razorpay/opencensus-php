<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class KotakReconciliation extends Base
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

    protected function addMailData()
    {
        $subject = $this->getSubject();

        $this->data['subject'] = $subject;

        $this->with($this->data);

        return $this;
    }

    protected function getSubject()
    {
        return 'Re: Kotak Settlement files for ' . $this->data['date'];
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }
}
