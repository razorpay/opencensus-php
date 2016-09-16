<?php

namespace RZP\Gateway\FirstData;

final class Codes
{
    const FIRST_DATA_HASH_ALGORITHM = 'SHA1';

    const ENGLISH_UK_LANG_CODE_CONNECT = 'en_GB';
    const ENGLISH_UK_LANG_CODE_API = 'en';

    const DATE_TIME_FORMAT = 'Y:m:d-H:i:s';

    const PAYMENT_MODE_PAYONLY = 'payonly';
    const PAYMENT_MODE_PAYPLUS = 'payplus';
    const PAYMENT_MODE_FULLPAY = 'fullpay';

    const TXN_TYPE_SALE     = 'sale';
    const TXN_TYPE_AUTH     = 'preauth';
    const TXN_TYPE_CAPTURE  = 'postauth';
    const TXN_TYPE_VOID     = 'void';
    const TXN_TYPE_REFUND   = 'return';

    const STATUS_AUTHORIZED                = 'authorized';
    const STATUS_AUTHORIZE_FAILED          = 'authorize_failed';
    const STATUS_CAPTURED                  = 'captured';
    const STATUS_CAPTURE_FAILED            = 'capture_failed';
    const STATUS_CREATED                   = 'created';
    const STATUS_REFUNDED                  = 'refunded';
    const STATUS_REFUND_FAILED             = 'refund_failed';

    public static $txnTypes = array(
        self::TXN_TYPE_SALE,
        self::TXN_TYPE_AUTH,
        self::TXN_TYPE_CAPTURE,
        self::TXN_TYPE_VOID,
    );

    public static $paymentModes = array(
        self::PAYMENT_MODE_PAYONLY,
        self::PAYMENT_MODE_PAYPLUS,
        self::PAYMENT_MODE_FULLPAY,
    );

    public static $amountEntity = array(
        self::TXN_TYPE_CAPTURE     =>  'payment',
        self::TXN_TYPE_REFUND      =>  'refund',
    );
}
