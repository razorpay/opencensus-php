<?php
namespace RZP\Mail\Merchant\InternationalEnablement;

class RejectedSafeMerchant extends Request
{
    protected function addHtmlView()
    {
        $this->view('emails.merchant.international_enablement_request.rejected_merchant_looks_safe');

        return $this;
    }
}
