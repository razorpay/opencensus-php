<?php

namespace RZP\Gateway\FirstData;

class PaymentMode
{
    const PAYONLY = 'payonly';
    const PAYPLUS = 'payplus';
    const FULLPAY = 'fullpay';

    const MODE_LIST = [
        self::PAYONLY,
        self::PAYPLUS,
        self::FULLPAY,
    ];
}
