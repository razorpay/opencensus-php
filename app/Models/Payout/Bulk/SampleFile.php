<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Models\Merchant;
use RZP\Models\WalletAccount;

class SampleFile extends Base
{
    protected function getInputEntries(Merchant\Entity $merchant)
    {
        $isMerchantDisabledForAmazonpay = (new WalletAccount\Service)->isWalletAccountAmazonPayFeatureDisabled();

        if ($isMerchantDisabledForAmazonpay === false)
        {
            return self::FILE_DATA_WITH_AMAZON_PAY;
        }

        return self::FILE_DATA;
    }
}
