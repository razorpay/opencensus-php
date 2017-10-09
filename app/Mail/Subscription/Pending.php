<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class Pending extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.pending_text');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.subscription.pending');

        return $this;
    }

    protected function getAction()
    {
        return 'Subscription Charge Failed';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_PENDING;
    }
}
