<?php

namespace RZP\Mail\Settlement;

use RZP\Constants\MailTags;

class KotakReconciliation extends Base
{
    protected function getFromHeader()
    {
        return 'Kotak Settlement';
    }

    protected function getSubject()
    {
        return "Re: Kotak Settlement files for $this->data['date']";
    }

    protected function getMailTag()
    {
        return MailTags::KOTAK_SETTLEMENT_FILES;
    }
}
