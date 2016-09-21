<?php

namespace RZP\Gateway\FirstData;

final class Codes
{
    const FIRST_DATA_HASH_ALGORITHM = 'SHA1';

    const ENGLISH_UK_LANG_CODE_CONNECT  = 'en_GB';
    const ENGLISH_UK_LANG_CODE_API      = 'en';

    const DATE_TIME_FORMAT = 'Y:m:d-H:i:s';

    const PAYMENT_MODE_PAYONLY = 'payonly';
    const PAYMENT_MODE_PAYPLUS = 'payplus';
    const PAYMENT_MODE_FULLPAY = 'fullpay';

    public static $paymentModes = array(
        self::PAYMENT_MODE_PAYONLY,
        self::PAYMENT_MODE_PAYPLUS,
        self::PAYMENT_MODE_FULLPAY,
    );
}
