<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail as MerchantDetail;


class LinkedAccount
{
    /**
     * Returns input for account(actually sub merchant) creation
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getAccountInput(array $entry): array
    {
        return [
            Merchant\Entity::ID   => Merchant\Entity::generateUniqueId(),
            Merchant\Entity::NAME => $entry[Header::BUSINESS_NAME],
        ];
    }

    /**
     * Returns input for account details (actually merchant details) creation
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getAccountDetailInput(array $entry): array
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

    /**
     * Returns bank account creation/update input. Used when a file row has
     * account id and in that case we just need to patch account details.
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getBankAccountDetailInput(array $entry): array
    {
        return [
            BankAccount\Entity::BENEFICIARY_NAME => $entry[Header::BANK_ACCOUNT_NAME],
            BankAccount\Entity::IFSC_CODE        => $entry[Header::BANK_BRANCH_IFSC],
            BankAccount\Entity::ACCOUNT_NUMBER   => $entry[Header::BANK_ACCOUNT_NUMBER],
        ];
    }
}
