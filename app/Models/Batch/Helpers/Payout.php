<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Payout as P;
use RZP\Models\Customer;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\FundTransfer\Attempt\Purpose;

class Payout
{
    public static function getCustomerCreateInput(array $entry): array
    {
        return [
            Customer\Entity::NAME    => $entry[Header::PAYOUT_CUSTOMER_NAME],
            Customer\Entity::CONTACT => $entry[Header::PAYOUT_CUSTOMER_CONTACT],
            Customer\Entity::EMAIL   => $entry[Header::PAYOUT_CUSTOMER_EMAIL],
        ];
    }

    public static function getBankAccountCreateInput(array $entry): array
    {
        $name = preg_replace('/[^a-zA-Z0-9 ]+/', '', $entry[Header::PAYOUT_CUSTOMER_NAME]);

        return [
            BankAccount\Entity::BENEFICIARY_NAME => substr($name, 0, 39),
            BankAccount\Entity::ACCOUNT_NUMBER   => $entry[Header::PAYOUT_BANK_ACCOUNT_NUMBER],
            BankAccount\Entity::IFSC_CODE        => $entry[Header::PAYOUT_BANK_IFSC],
        ];
    }

    public static function getPayoutCreateInput(
        array $entry,
        BankAccount\Entity $bankAccount,
        Customer\Entity $customer): array
    {
        $requestArray = [
            P\Entity::PURPOSE     => Purpose::REFUND,
            P\Entity::CUSTOMER_ID => $customer->getPublicId(),
            P\Entity::DESTINATION => $bankAccount->getPublicId(),
            P\Entity::METHOD      => $entry[Header::PAYOUT_METHOD],
            P\Entity::AMOUNT      => $entry[Header::PAYOUT_AMOUNT],
            P\Entity::CURRENCY    => $entry[Header::PAYOUT_CURRENCY],
            P\Entity::NOTES       => json_decode($entry[Header::PAYOUT_NOTES], true) ?? [],
        ];

        return $requestArray;
    }
}
