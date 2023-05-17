<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Models\Payment\Processor\PayLater;
use RZP\Models\Payment\Processor\CardlessEmi;

class BankCodes
{
    public static $bankCodeMap = [
        PayLater::HDFC => '11460',                      // HDFC Bank
        PayLater::KKBK => '19090',                      // KOTAK Bank
    ];

    public static $cardlessEmiBankCodeMap = [
        CardlessEmi::HDFC => '10790',                      // HDFC Bank
        CardlessEmi::IDFB => '10770',                      // IDFC First Bank
        CardlessEmi::KKBK => '10800',                      // Kotak Bank
        CardlessEmi::FDRL => '10780',                      // Federal Bank
        CardlessEmi::ICIC => '14360',                      // ICICI Bank
        CardlessEmi::HCIN => '13860',                      // Home Credit
        CardlessEmi::BARB => '19080',                      // Bank of baroda
        CardlessEmi::KRBE => '20240',                      // Kredit Bee
        CardlessEmi::CSHE => '20400',                      // CASHe
        CardlessEmi::TVSC => '20600',                      // TVS Credit
    ];

    public static function getBankCode($ifsc)
    {
        return self::$bankCodeMap[$ifsc];
    }

    public static function getBankCodeForCardlessEmiMultiLender($ifsc)
    {
        return self::$cardlessEmiBankCodeMap[$ifsc];
    }
}
