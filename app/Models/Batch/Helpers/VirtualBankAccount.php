<?php

namespace RZP\Models\Batch\Helpers;

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
        return [
            VirtualAccount\Entity::DESCRIPTOR  => $entry[Header::VA_DESCRIPTOR],
            VirtualAccount\Entity::CUSTOMER_ID => $customer->getPublicId(),
            VirtualAccount\Entity::RECEIVERS => [
                VirtualAccount\Entity::TYPES => [
                    VirtualAccount\Receiver::BANK_ACCOUNT,
                ],
                VirtualAccount\Entity::BANK_ACCOUNT => [
                    VirtualAccount\Receiver::NUMERIC    => true,
                    // Descriptor cannot be used with numeric accounts.
                    // Uncomment when api#6587 is merged.
                    // VirtualAccount\Receiver::DESCRIPTOR => $entry[Header::VA_DESCRIPTOR],
                ],
            ],
        ];
    }
}
