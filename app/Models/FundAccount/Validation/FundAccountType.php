<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;

class FundAccountType
{
    const BANK_ACCOUNT = 'bank_account';
    const VPA = 'vpa';

    public static $allowedTypes = [
        self::BANK_ACCOUNT,
        self::VPA,
    ];

    public static function validate(string $method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid Fund Account Type: ' . $method);
        }
    }
}
