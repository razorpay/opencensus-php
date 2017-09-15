<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class Halted extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.halted_text');

        return $this;
    }

    protected function getResult()
    {
        return 'Subscription halted';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_HALTED;
    }
}
