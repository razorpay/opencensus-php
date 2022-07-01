<?php

namespace RZP\Models\Transaction\Statement\Ledger\Journal;

use RZP\Constants\Entity as E;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const PAYOUT                  = 'payout';
    const REVERSAL                = 'reversal';
    const EXTERNAL                = 'external';
    const ADJUSTMENT              = 'adjustment';
    const BANK_TRANSFER           = 'bank_transfer';
    const CREDIT_TRANSFER         = 'credit_transfer';
    const FUND_ACCOUNT_VALIDATION = 'fund_account_validation';

    const BANKING_TYPE = [
        self::PAYOUT,
        self::BANK_TRANSFER,
        self::REVERSAL,
        self::ADJUSTMENT,
        self::EXTERNAL,
        self::CREDIT_TRANSFER,
        self::FUND_ACCOUNT_VALIDATION,
    ];

    public static function validateType(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        if (strpos($key, '.') !== false)
        {
            $key = str_replace('.', '_', $key);
        }

        if ((defined($key) === false) or (constant($key) !== $type))
        {
            throw new InvalidArgumentException("Not a valid Transaction type: {$type}");
        }
    }

    public static function validateBankingType(string $type)
    {
        self::validateType($type);

        if (in_array($type, self::BANKING_TYPE, true) === false)
        {
            throw new BadRequestValidationFailureException("Not a valid banking transaction type : " . $type);
        }
    }

    public static function getEntityClass(string $type): string
    {
        self::validateType($type);

        return E::getEntityClass($type);
    }
}
