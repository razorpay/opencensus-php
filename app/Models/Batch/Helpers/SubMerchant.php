<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail as MerchantDetail;


class SubMerchant
{
    /**
     * Returns input for sub merchant creation
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getSubMerchantInput(array $entry): array
    {
        return [
            Merchant\Entity::ID    => Merchant\Entity::generateUniqueId(),
            Merchant\Entity::NAME  => $entry[Header::MERCHANT_NAME],
            Merchant\Entity::EMAIL => $entry[Header::MERCHANT_EMAIL],
        ];
    }

    /**
     * Returns input for sub merchant detail entity creation
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getSubMerchantDetailInput(array $entry): array
    {
        return [
            MerchantDetail\Entity::BANK_ACCOUNT_NAME   => $entry[Header::BANK_ACCOUNT_NAME],
            MerchantDetail\Entity::BANK_BRANCH_IFSC    => $entry[Header::BANK_BRANCH_IFSC],
            MerchantDetail\Entity::BANK_ACCOUNT_NUMBER => $entry[Header::BANK_ACCOUNT_NUMBER],
            MerchantDetail\Entity::BANK_ACCOUNT_TYPE   => $entry[Header::BANK_ACCOUNT_TYPE],
            MerchantDetail\Entity::BUSINESS_NAME       => $entry[Header::BUSINESS_NAME],
            MerchantDetail\Entity::BUSINESS_TYPE       => 1,
            MerchantDetail\Entity::SUBMIT              => '1',
        ];
    }
}
