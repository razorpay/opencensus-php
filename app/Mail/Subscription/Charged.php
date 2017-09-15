<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class Charged extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.charged_text');

        return $this;
    }

    protected function getResult()
    {
        return 'Subscription charged successfully';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_CHARGED;
    }
}
