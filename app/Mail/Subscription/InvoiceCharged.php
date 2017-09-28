<?php

namespace RZP\Mail\Subscription;

use RZP\Constants\MailTags;

class InvoiceCharged extends Base
{
    protected function addTextView()
    {
        $this->text('emails.subscription.invoice_charged_text');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.subscription.invoice_charged');

        return $this;
    }

    protected function getAction()
    {
        return 'Subscription Invoice Charged';
    }

    protected function getMailTag()
    {
        return MailTags::SUBSCRIPTION_INVOICE_CHARGED;
    }
}
