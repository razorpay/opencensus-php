<?php

namespace RZP\Mail\Merchant\InternationalEnablement;

class Accepted extends Request
{
    protected function addHtmlView()
    {
        $this->view('emails.merchant.international_enablement_request.accepted');

        return $this;
    }
}
