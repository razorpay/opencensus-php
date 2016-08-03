<?php

namespace RZP\Gateway\FirstData;

final class Codes
{
    const HASH_ALGORITHM_SHA256 = 'SHA256';

    const ENGLISH_UK_LANG_CODE = 'en_GB';

    const DATE_TIME_FORMAT = 'Y:m:d-h:m:s';

    const PAYMENT_MODE_PAYONLY = 'payonly';
    const PAYMENT_MODE_PAYPLUS = 'payplus';
    const PAYMENT_MODE_FULLPAY = 'fullpay';

    const TXNTYPE_SALE      = 'sale';
    const TXNTYPE_PREAUTH   = 'preauth';
    const TXNTYPE_POSTAUTH  = 'postauth';
    const TXNTYPE_VOID      = 'void';

    public static $txnTypes = array(
        self::TXNTYPE_SALE,
        self::TXNTYPE_PREAUTH,
        self::TXNTYPE_POSTAUTH,
        self::TXNTYPE_VOID,
    );

    public static $paymentModes = array(
        self::PAYMENT_MODE_PAYONLY,
        self::PAYMENT_MODE_PAYPLUS,
        self::PAYMENT_MODE_FULLPAY,
    );
}
