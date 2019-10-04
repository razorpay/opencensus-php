<?php

namespace RZP\Models\Payment;

use RZP\Models\Feature;
use RZP\Exception\BadRequestValidationFailureException;

class Flow
{
    const HEADLESS_OTP = 'headless_otp';
    const INTENT       = 'intent';
    const COLLECT      = 'collect';

    public static $flows = [
        Method::CARD => [
            self::HEADLESS_OTP,
        ],
        Method::UPI  => [
            self::INTENT,
            self::COLLECT,
        ],
    ];

    public static function getFlowForMethod($method)
    {
        return self::$flows[$method];
    }
}
