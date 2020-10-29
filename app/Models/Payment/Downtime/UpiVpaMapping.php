<?php

namespace RZP\Models\Payment\Downtime;

use RZP\Gateway\Upi\Base;

class UpiVpaMapping
{
    protected static $singleVpaPspMapping = [
        Base\ProviderCode::UPI           => Base\ProviderPsp::BHIM,
        Base\ProviderCode::PAYTM         => Base\ProviderPsp::PAYTM,
        Base\ProviderCode::YBL           => Base\ProviderPsp::PHONEPE,
        Base\ProviderCode::APL           => Base\ProviderPsp::AMAZON_PAY,
    ];

    protected static $multiplePspVpaMapping = [
        Base\ProviderPsp::GOOGLE_PAY => [
            Base\ProviderCode::OKAXIS,
            Base\ProviderCode::OKHDFCBANK,
            Base\ProviderCode::OKICICI,
            Base\ProviderCode::OKSBI,
        ]
    ];

    public static function getPsp($vpaHandle)
    {
        return self::$singleVpaPspMapping[$vpaHandle] ?? null;
    }

    public static function getMultiplePspVpaMapping()
    {
        return self::$multiplePspVpaMapping;
    }
}
