<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail as MerchantDetail;


class LinkedAccount
{
    public static function getCreateAccountInput(array $entry): array
    {
        return [
            Merchant\Entity::ID   => Merchant\Entity::generateUniqueId(),
            Merchant\Entity::NAME => $entry[Header::BUSINESS_NAME],
        ];
    }

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
}
