<?php

namespace RZP\Gateway\FirstData;

class TxnType
{
    const SALE     = 'sale';
    const AUTH     = 'preauth';
    const CAPTURE  = 'postauth';
    const VOID     = 'void';
    const REFUND   = 'return';

    public static $list = array(
        self::SALE,
        self::AUTH,
        self::CAPTURE,
        self::VOID,
    );

    public static $amountEntity = array(
        self::CAPTURE     =>  'payment',
        self::REFUND      =>  'refund',
    );
}
