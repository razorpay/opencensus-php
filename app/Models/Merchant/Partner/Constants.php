<?php

namespace RZP\Models\Merchant\Partner;

use RZP\Models\Merchant;

class Constants
{
    const BANK                                    = 'bank';
    const PARTNER                                 = 'partner';
    const RESELLER                                = 'reseller';
    const AGGREGATOR                              = 'aggregator';
    const FULLY_MANAGED                           = 'fully_managed';
    const PURE_PLATFORM                           = 'pure_platform';

    public static $partnerTypes = [
        self::BANK,
        self::RESELLER,
        self::AGGREGATOR,
        self::FULLY_MANAGED,
        self::PURE_PLATFORM,
    ];
}
