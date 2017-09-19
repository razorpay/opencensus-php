<?php

namespace RZP\Models\Settlement\Details;

use RZP\Exception;

class Component
{
    const REFUND        = 'refund';
    const PAYOUT        = 'payout';
    const PAYMENT       = 'payment';
    const ADJUSTMENT    = 'adjustment';
    const FEE           = 'fee';
    const SERVICE_TAX   = 'service_tax';
    const FEE_CREDITS   = 'fee_credits';
    const TRANSFER      = 'transfer';
    const REVERSAL      = 'reversal';
    const DISPUTE       = 'dispute';

    public static function validateComponent(string $component)
    {
        if (defined(__CLASS__ . '::' . strtoupper($component)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid settlement component: ' . $component);
        }
    }

    public static function getAllComponents()
    {
        return [
            self::PAYMENT,
            self::REFUND,
            self::ADJUSTMENT,
            self::PAYOUT,
            self::SERVICE_TAX,
            self::FEE,
            self::FEE_CREDITS,
            self::TRANSFER,
            self::REVERSAL,
            self::DISPUTE,
        ];
    }
}
