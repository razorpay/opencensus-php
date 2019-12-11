<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Vpa;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\Contact as ContactModel;
use RZP\Models\FundAccount as FundAccountModel;
use RZP\Exception\BadRequestValidationFailureException;

class FundAccount
{
    public static function getFundAccountInput(array & $entry, ContactModel\Entity $contact = null): array
    {
        $input = [
            FundAccountModel\Entity::ACCOUNT_TYPE => $entry[Header::FUND_ACCOUNT_TYPE],
        ];

        if ($contact !== null)
        {
            $input[FundAccountModel\Entity::CONTACT_ID] = $contact->getPublicId();
        }

        // Per fund account type, prepares details key input for fund account's core.
        switch ($entry[Header::FUND_ACCOUNT_TYPE])
        {
            case FundAccountModel\Type::BANK_ACCOUNT:
                $input[FundAccountModel\Entity::BANK_ACCOUNT] = [
                    BankAccount\Entity::IFSC           => $entry[Header::FUND_ACCOUNT_IFSC],
                    BankAccount\Entity::ACCOUNT_NUMBER => $entry[Header::FUND_ACCOUNT_NUMBER],
                    BankAccount\Entity::NAME           => $entry[Header::FUND_ACCOUNT_NAME],
                ];
                break;

            case FundAccountModel\Type::VPA:
                $input[FundAccountModel\Entity::VPA] = [
                    Vpa\Entity::ADDRESS => $entry[Header::FUND_ACCOUNT_VPA],
                ];
                break;

            default:
                throw new BadRequestValidationFailureException(
                    "Invalid value for fund account type - {$entry[Header::FUND_ACCOUNT_TYPE]}",
                    Header::FUND_ACCOUNT_TYPE);
        }

        return $input;
    }
}
