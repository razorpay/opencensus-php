<?php

namespace RZP\Models\Pricing;

use RZP\Exception;

class Feature
{
    const PAYMENT           = 'payment';
    const PAYOUT            = 'payout';
    const RECURRING         = 'recurring';

    public static function validateFeature($feature)
    {
        if (defined(__CLASS__.'::'.strtoupper($feature)) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Pricing feature: ' . $feature);
        }
    }
}
