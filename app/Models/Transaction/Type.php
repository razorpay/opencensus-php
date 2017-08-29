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
        $entity = 'RZP\\Models\\' . ucfirst($type) . '\Entity';

        if ($type === self::REFUND)
            $entity = 'RZP\\Models\\Payment\\' . ucfirst($type) . '\Entity';

        return $entity;
    }
}
