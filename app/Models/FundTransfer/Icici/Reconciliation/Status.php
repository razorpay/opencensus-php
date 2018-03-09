<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

class Status
{
    const PAID                  = 'Paid';
    const CANCELLED             = 'Cancelled';
    const PENDING               = 'Pending Processing';
    const AWAITING_MESSAGING    = 'Awaiting Messaging';
    const AWAITING_LIQUIDATION  = 'Awaiting Liquidation';

    const SUCCESS_STATUS = [
        self::PAID,
        self::PENDING,
        self::AWAITING_MESSAGING,
        self::AWAITING_LIQUIDATION,
    ];

}
