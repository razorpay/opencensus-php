<?php

namespace RZP\Gateway\FirstData;

class TxnType
{
    const SALE     = 'sale';
    const AUTH     = 'preauth';
    const CAPTURE  = 'postauth';
    const VOID     = 'void';
    const REFUND   = 'return';

    public static $typeList = [
        self::SALE,
        self::AUTH,
        self::CAPTURE,
        self::VOID,
    ];

    // Indicates which entity to use to select charge_total
    // If a refund request, use the refund entity, otherwise use payment
    public static $amountEntity = [
        self::CAPTURE     =>  'payment',
        self::REFUND      =>  'refund',
    ];
}
