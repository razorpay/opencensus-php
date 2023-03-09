<?php

namespace RZP\Mail\Merchant\Capital\LineOfCredit;

use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForX;

class CreateSubMerchantAffiliate extends CreateSubMerchantAffiliateForX
{
    protected function addSubject()
    {
        $this->subject('[IMP] ' . $this->aggregator['name'] . ' has invited you to apply for ‘Razorpay Line of Credit’');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.capital.line_of_credit.add_sub_merchant_affiliate');

        return $this;
    }
}
