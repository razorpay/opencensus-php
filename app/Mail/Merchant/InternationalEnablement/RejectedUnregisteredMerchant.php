<?php
namespace RZP\Mail\Merchant\InternationalEnablement;

class RejectedUnregisteredMerchant extends Request
{
    protected function addHtmlView()
    {
        $this->view('emails.merchant.international_enablement_request.rejected_unregistered_merchant');

        return $this;
    }
}
