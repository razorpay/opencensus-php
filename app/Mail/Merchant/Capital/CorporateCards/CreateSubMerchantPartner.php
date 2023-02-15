<?php

namespace RZP\Mail\Merchant\Capital\CorporateCards;

use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantPartner as CreateSubMerchantPartnerForX;

class CreateSubMerchantPartner extends CreateSubMerchantPartnerForX
{
    protected function addHtmlView(): CreateSubMerchantPartner
    {
        $this->view('emails.merchant.capital.corporate_cards.add_sub_merchant_mail_partner');

        return $this;
    }
}
