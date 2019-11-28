<?php

namespace App\Merchant\CustomNotes;

class Constants
{
    const   TEST_MID = '10000000000000',
            BHARTI_AXA = 'D2BsrUJVg04abr';

    const DEFAULT_EXPIRY = [
        self::BHARTI_AXA => 72,
        self::TEST_MID => 72
    ];

    public static function getDefaultExpiryTimeForPaymentLinksByMID($mid)
    {
        return self::DEFAULT_EXPIRY[$mid] ?? null;
    }
}
