<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Vpa;
use RZP\Models\Contact;
use RZP\Models\BankAccount;
use RZP\Models\FundAccount;
use RZP\Exception\BadRequestValidationFailureException;

class BatchHelper
{
    const ID             = 'id';
    const TYPE           = 'account_type';
    const IFSC           = 'account_IFSC';
    const NUMBER         = 'account_number';
    const NAME           = 'account_name';
    const VPA            = 'account_vpa';
    const FUND_ACCOUNT   = 'fund';

    public static function getFundAccountInput(array $entry, Contact\Entity $contact): array
    {
        $fundAccountType = $entry[self::FUND_ACCOUNT][self::TYPE];

        $input = [
            Entity::ACCOUNT_TYPE => $fundAccountType,
        ];

        $input[FundAccount\Entity::CONTACT_ID] = $contact->getPublicId();

        // Per fund account type, prepares details key input for fund account's core.
        switch ($fundAccountType)
        {
            case Type::BANK_ACCOUNT:
                $input[Entity::BANK_ACCOUNT] = [
                    BankAccount\Entity::IFSC           => $entry[self::FUND_ACCOUNT][self::IFSC],
                    BankAccount\Entity::ACCOUNT_NUMBER => trim($entry[self::FUND_ACCOUNT][self::NUMBER]),
                    BankAccount\Entity::NAME           => $entry[self::FUND_ACCOUNT][self::NAME],
                ];
                break;

            case Type::VPA:
                $input[Entity::VPA] = [
                    Vpa\Entity::ADDRESS => $entry[self::FUND_ACCOUNT][self::VPA],
                ];
                break;

            default:
                throw new BadRequestValidationFailureException(
                    "Invalid value for fund account type - $fundAccountType",
                    self::TYPE,
                    $input);
        }

        $input[Entity::IDEMPOTENCY_KEY] = $entry[Entity::IDEMPOTENCY_KEY];

        return $input;
    }
}
