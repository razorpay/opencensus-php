<?php

namespace RZP\Models\Transaction;

use RZP\Exception;

class Type
{
    const REFUND        = 'refund';
    const PAYOUT        = 'payout';
    const PAYMENT       = 'payment';
    const DISPUTE       = 'dispute';
    const TRANSFER      = 'transfer';
    const REVERSAL      = 'reversal';
    const ADJUSTMENT    = 'adjustment';
    const SETTLEMENT    = 'settlement';
    const BANK_TRANSFER = 'bank_transfer';

    //
    // These entities from transaction will not be considered for merchant invoice as we wont charge on these entities
    // - Payment also a part of this list because payment fee is calculated from payments table in different query.
    //   so no need to consider payments here
    //
    const IGNORE_ENTITIES_FROM_MERCHANT_INVOICE = [
        self::PAYMENT,
        self::REFUND,
        self::DISPUTE,
        self::REVERSAL,
        self::SETTLEMENT,
        self::ADJUSTMENT,
        self::BANK_TRANSFER,
    ];

    public static function validateType($type)
    {
        if (defined(__CLASS__.'::'.strtoupper($type)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Transaction type: ' . $type);
        }
    }

    public static function getEntityClass($type)
    {
        $studlyType = studly_case($type);

        $entity = 'RZP\\Models\\' . $studlyType . '\Entity';

        if ($type === self::REFUND)
            $entity = 'RZP\\Models\\Payment\\' . $studlyType . '\Entity';

        return $entity;
    }
}
