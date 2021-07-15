<?php

namespace RZP\Models\Payout\Bulk;

use RZP\Models\Batch;
use RZP\Models\Merchant;
use RZP\Models\WalletAccount;

class TemplateFile extends Base
{
    protected function getInputEntries(Merchant\Entity $merchant)
    {
        $isMerchantDisabledForAmazonpay = (new WalletAccount\Service)->isWalletAccountAmazonPayFeatureDisabled();

        if ($isMerchantDisabledForAmazonpay === false)
        {
            $inputData = self::FILE_DATA_WITH_AMAZON_PAY;
        }
        else
        {
            $inputData = self::FILE_DATA;
        }

        if ($merchant->hasDirectBankingBalance() === true)
        {
            $accountNumber = $merchant->directBankingBalances->first()->getAccountNumber();
        }
        else
        {
            $accountNumber = $merchant->sharedBankingBalance->getAccountNumber();
        }

        for ($i = 0; $i < sizeof($inputData); $i++)
        {
            $inputData[$i][Batch\Header::RAZORPAYX_ACCOUNT_NUMBER] = $accountNumber;
        }

        return $inputData;
    }
}
