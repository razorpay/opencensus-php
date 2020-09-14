<?php

namespace RZP\Models\Payment\Refund;

class Speed
{
    const INSTANT = 'instant';
    const OPTIMUM = 'optimum';
    const NORMAL  = 'normal';

    const REFUND_MERCHANT_ALLOWED_INSTANT_SPEEDS = [
        self::OPTIMUM,
    ];

    const REFUND_INSTANT_SPEEDS = [
        self::OPTIMUM,
        self::INSTANT,
    ];
}
