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
        $subject = "Re: Kotak Settlement files for $this->data['date']";

        $this->subject($subject);

        return $this;
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }
}
