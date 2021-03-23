<?php

namespace RZP\Mail\Merchant\InternationalEnablement;

class RejectedRiskyMerchant extends Request
{
    protected function addHtmlView()
    {
        $this->view('emails.merchant.international_enablement_request.rejected_merchant_looks_risky');

        return $this;
    }
}
