<?php

namespace RZP\Gateway\FirstData;

class TxnType
{
    const SALE     = 'sale';
    const AUTH     = 'preauth';
    const CAPTURE  = 'postauth';
    const REVERSE  = 'void';
    const REFUND   = 'return';
    const PERIODIC = 'periodic';

    // Indicates which entity to use to select charge_total
    // If a refund request, use the refund entity, otherwise use payment
    public static $amountEntity = [
        self::CAPTURE => 'payment',
        self::SALE    => 'payment',
        self::REFUND  => 'refund',
    ];
}
