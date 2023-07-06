<?php

namespace RZP\Models\Transaction;

use RZP\Constants\Entity as E;
use RZP\Exception\InvalidArgumentException;
use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const REFUND                  = 'refund';
    const PAYOUT                  = 'payout';
    const PAYMENT                 = 'payment';
    const DISPUTE                 = 'dispute';
    const TRANSFER                = 'transfer';
    const REVERSAL                = 'reversal';
    const EXTERNAL                = 'external';
    const ADJUSTMENT              = 'adjustment';
    const SETTLEMENT              = 'settlement';
    const COMMISSION              = 'commission';
    const BANK_TRANSFER           = 'bank_transfer';
    const SETTLEMENT_ONDEMAND     = 'settlement.ondemand';
    const SETTLEMENT_TRANSFER     = 'settlement_transfer';
    const FUND_ACCOUNT_VALIDATION = 'fund_account_validation';
    const CREDIT_REPAYMENT        = 'credit_repayment';
    const INSTALLMENT             = 'installment';
    const CHARGE                  = 'charge';
    const REPAYMENT_BREAKUP       = 'repayment_breakup';
    const INTEREST_WAIVER         = 'interest_waiver';
    const CREDIT_TRANSFER         = 'credit_transfer';

    //
    // These entities from transaction will not be considered for merchant invoice as we wont charge on these entities
    // - Payment also a part of this list because payment fee is calculated from payments table in different query.
    //   so no need to consider payments here
    // - Fund Account Validation is also a part of this list because It is computed in different line item.
    // - Refund is also a part of this list because instant refunds fee is computed in different query
    //
    const IGNORE_ENTITIES_FROM_MERCHANT_INVOICE = [
        self::PAYMENT,
        self::REFUND,
        self::DISPUTE,
        self::REVERSAL,
        self::EXTERNAL,
        self::SETTLEMENT,
        self::ADJUSTMENT,
        self::COMMISSION,
        self::BANK_TRANSFER,
        self::FUND_ACCOUNT_VALIDATION,
        self::CREDIT_TRANSFER
    ];

    const BANKING_TYPE = [
        self::PAYOUT,
        self::BANK_TRANSFER,
        self::REVERSAL,
        self::ADJUSTMENT,
        self::EXTERNAL,
        self::FUND_ACCOUNT_VALIDATION,
        self::CREDIT_TRANSFER
    ];

    const CAPITAL_TYPE = [
        self::REPAYMENT_BREAKUP,
        self::INSTALLMENT,
        self::CHARGE,
        self::INTEREST_WAIVER
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
