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

    protected function addHtmlView()
    {
        $this->view('emails.subscription.charged');

        return $this;
    }

    protected function getAction()
    {
        return 'Subscription Charged Successfully';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_CHARGED;
    }
}
