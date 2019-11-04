<?php

namespace RZP\Models\Transaction;

use RZP\Constants\Entity as E;
use RZP\Exception\InvalidArgumentException;

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
    const BANK_TRANSFER           = 'bank_transfer';
    const COMMISSION              = 'commission';
    const FUND_ACCOUNT_VALIDATION = 'fund_account_validation';

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
    ];

    const IGNORE_ENTITIES_FROM_MERCHANT_BANKING_INVOICE = [
        self::PAYMENT,
        self::REFUND,
        self::DISPUTE,
        self::REVERSAL,
        self::EXTERNAL,
        self::SETTLEMENT,
        self::ADJUSTMENT,
        self::COMMISSION,
    ];

    public static function validateType(string $type)
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        if ((defined($key) === false) or (constant($key) !== $type))
        {
            throw new InvalidArgumentException("Not a valid Transaction type: {$type}");
        }
    }

    public static function getEntityClass(string $type): string
    {
        self::validateType($type);

        return E::getEntityClass($type);
    }
}
