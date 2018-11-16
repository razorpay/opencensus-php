<?php

namespace RZP\Gateway\Billdesk;

class RefundStatus
{
    const CANCELLED = '0699';
    const REFUNDED  = '0799';
    const NA        = 'NA';

    public static $statusMap = array(
        self::CANCELLED => 'cancelled',
        self::REFUNDED  => 'refunded',
        self::NA        => null);
}
