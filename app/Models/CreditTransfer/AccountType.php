<?php


namespace RZP\Models\CreditTransfer;

use RZP\Exception;

class AccountType
{
    const BANK_ACCOUNT = "bank_account";

    public static function getSupportedVaToVaCreditAccountTypes()
    {
        return [
            self::BANK_ACCOUNT
        ];
    }

    public static function validate(string $accountType = null)
    {
        if (in_array($accountType, self::getSupportedVaToVaCreditAccountTypes(), true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid credit account type: ' . $accountType);
        }
    }
}
