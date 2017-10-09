<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class CardChanged extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.card_changed_text');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.subscription.card_changed');

        return $this;
    }

    protected function getAction()
    {
        return 'Subscription Card Updated';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_CARD_CHANGED;
    }
}
