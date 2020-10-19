<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Models\Payment\Processor\PayLater;

class BankCodes
{
    public static $bankCodeMap = [
        PayLater::HDFC => '11460',                      // HDFC Bank
    ];

    public static function getBankCode($ifsc)
    {
        return self::$bankCodeMap[$ifsc];
    }
}
