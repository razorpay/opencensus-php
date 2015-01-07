<?php

namespace Models\Transaction;

class Type
{
    const PAYMENT       = 'payment';
    const REFUND        = 'refund';
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