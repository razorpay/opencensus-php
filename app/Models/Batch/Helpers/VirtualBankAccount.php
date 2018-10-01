<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\BankAccount\Generator;
use RZP\Models\VirtualAccount;
use RZP\Models\Customer;
use RZP\Models\Batch\Header;

class VirtualBankAccount
{
    public static function getCustomerCreateInput(array $entry): array
    {
        return [
            Customer\Entity::NAME    => $entry[Header::VA_CUSTOMER_NAME],
            Customer\Entity::CONTACT => $entry[Header::VA_CUSTOMER_CONTACT],
            Customer\Entity::EMAIL   => $entry[Header::VA_CUSTOMER_EMAIL],
        ];
    }

    public static function getVirtualAccountCreateInput(array $entry, Customer\Entity $customer): array
    {
        $requestArray = [
            VirtualAccount\Entity::DESCRIPTION  => $entry[Header::VA_DESCRIPTION],
            VirtualAccount\Entity::CUSTOMER_ID  => $customer->getPublicId(),
            VirtualAccount\Entity::RECEIVERS    => [
                VirtualAccount\Entity::TYPES        => [
                    VirtualAccount\Receiver::BANK_ACCOUNT,
                ],
                VirtualAccount\Entity::BANK_ACCOUNT => [
                    Generator::NUMERIC => true,
                ],
            ],
            VirtualAccount\Entity::NOTES        => json_decode($entry[Header::VA_NOTES], true) ?? [],
        ];

        if (empty($entry[Header::VA_DESCRIPTOR]) === false)
        {
            $requestArray[VirtualAccount\Entity::RECEIVERS]
                [VirtualAccount\Entity::BANK_ACCOUNT]
                [Generator::DESCRIPTOR] = $entry[Header::VA_DESCRIPTOR];
        }

        return $requestArray;
    }
}
