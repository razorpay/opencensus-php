<?php

namespace RZP\Gateway\FirstData;

final class Codes
{
    const FIRST_DATA_HASH_ALGORITHM = 'SHA1';

    const ENGLISH_UK_LANG_CODE = 'en_GB';

    const DATE_TIME_FORMAT = 'Y:m:d-h:m:s';

    const PAYMENT_MODE_PAYONLY = 'payonly';
    const PAYMENT_MODE_PAYPLUS = 'payplus';
    const PAYMENT_MODE_FULLPAY = 'fullpay';

    const TXNTYPE_SALE      = 'sale';
    const TXNTYPE_PREAUTH   = 'preauth';
    const TXNTYPE_POSTAUTH  = 'postauth';
    const TXNTYPE_VOID      = 'void';

    const STATUS_AUTHORIZED                = 'authorized';
    const STATUS_AUTHORIZE_FAILED          = 'authorize_failed';
    const STATUS_CAPTURED                  = 'captured';
    const STATUS_CAPTURE_FAILED            = 'capture_failed';
    const STATUS_CREATED                   = 'created';
    const STATUS_REFUNDED                  = 'refunded';
    const STATUS_REFUND_FAILED             = 'refund_failed';

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
