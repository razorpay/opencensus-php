<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
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

    public static function getFailureStatus(): array
    {
        return [
            self::CANCELLED
        ];
    }

    public static function getSuccessfulStatus(): array {
        return [
            self::PAID,
            self::PENDING,
            self::AWAITING_MESSAGING,
            self::AWAITING_LIQUIDATION
        ];
    }
}
