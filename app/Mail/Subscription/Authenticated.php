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

    protected function addHtmlView()
    {
        $this->view('emails.subscription.authenticated');

        return $this;
    }

    protected function getAction()
    {
        return 'Subscription Initialized';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_AUTHENTICATED;
    }
}
