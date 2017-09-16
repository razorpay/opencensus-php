<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class Authenticated extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.authenticated_text');

        return $this;
    }

    protected function getResult()
    {
        return 'Subscription initialized';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_AUTHENTICATED;
    }
}
