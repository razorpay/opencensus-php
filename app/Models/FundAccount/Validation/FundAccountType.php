<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;

class FundAccountType
{
    const BANK_ACCOUNT = 'bank_account';

    public static $allowedTypes = [
        self::BANK_ACCOUNT,
    ];

    public static function validate(string $method)
    {
        if (defined(__CLASS__ . '::' . strtoupper($method)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Fund Account Type: ' . $method);
        }
    }
}
