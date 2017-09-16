<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class Cancelled extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.cancelled_text');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.subscription.cancelled');

        return $this;
    }

    protected function getResult()
    {
        return 'Subscription cancelled';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_CANCELLED;
    }
}
