<?php

namespace RZP\Services\Mock;

use RZP\Models\Merchant;

use RZP\Services\SalesForceClient as BaseSalesForceClient;

class SalesForceClient extends BaseSalesForceClient
{
    public function fetchAccountDetails($input = '')
    {
        return $input;
    }

    public function sendPreSignupDetails(array $input, Merchant\Entity $merchant)
    {
        return;
    }

    public function captureInterestOfPrimaryMerchantInBanking(Merchant\Entity $merchant)
    {
        return;
    }

    public function fetchAccessToken()
    {
        return '123';
    }
}
