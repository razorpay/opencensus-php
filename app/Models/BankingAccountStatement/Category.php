<?php

namespace RZP\Models\BankingAccountStatement;

use RZP\Exception\BadRequestValidationFailureException;

class Category
{
    const CUSTOMER_INITIATED = 'customer_initiated';
    const BANK_INITIATED     = 'bank_initiated';
    const CASH_WITHDRAWAL    = 'cash_withdrawal';
    const CASH_DEPOSIT       = 'cash_deposit';
    const OTHERS             = 'others';

    public static function isValid(string $category): bool
    {
        $key = __CLASS__ . '::' . strtoupper($category);

        return ((defined($key) === true) and (constant($key) === $category));
    }

    public static function validate(string $category)
    {
        if (self::isValid($category) === false)
        {
            throw new BadRequestValidationFailureException('Not a valid category: ' . $category);
        }
    }
}
