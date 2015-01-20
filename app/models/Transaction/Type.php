<?php

namespace Models\Transaction;

class Type
{
    const REFUND        = 'refund';
    const PAYMENT       = 'payment';
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
}