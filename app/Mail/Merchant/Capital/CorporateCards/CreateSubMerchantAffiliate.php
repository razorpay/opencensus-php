<?php

namespace RZP\Mail\Merchant\Capital\CorporateCards;

use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantAffiliate as CreateSubMerchantAffiliateForX;

class CreateSubMerchantAffiliate extends CreateSubMerchantAffiliateForX
{
    protected function addSubject()
    {
        $this->subject('[IMP] ' . $this->aggregator['name'] . ' has invited you to join RazorpayX Corporate Cards!');

        return $this;
    }

    protected function addHtmlView()
    {
        $this->view('emails.merchant.capital.corporate_cards.add_sub_merchant_affiliate');

        return $this;
    }
}
