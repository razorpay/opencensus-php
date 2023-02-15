<?php

namespace RZP\Mail\Merchant\Capital\LineOfCredit;

use RZP\Mail\Merchant\RazorpayX\CreateSubMerchantPartner as CreateSubMerchantPartnerForX;

class CreateSubMerchantPartner extends CreateSubMerchantPartnerForX
{
    protected function addHtmlView(): CreateSubMerchantPartner
    {
        $this->view('emails.merchant.capital.line_of_credit.add_sub_merchant_mail_partner');

        return $this;
    }
}
