<?php

namespace RZP\Models\Payout;

use RZP\Constants;
use RZP\Error\ErrorCode;
use RZP\Models\Settlement;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Balance\AccountType;

class QueuedReasons
{
    const BENE_BANK_DOWN       = 'beneficiary_bank_down';
    const NPCI_DOWN            = 'npci_system_down';
    const LOW_BALANCE          = 'low_balance';
    const NEFT_LIMIT_EXHAUSTED = 'neft_limit_exhausted';
    const NEFT_WINDOW_CLOSED   = 'neft_window_closed';

    protected static $queuedReasons = [
        self::BENE_BANK_DOWN,
        self::NPCI_DOWN,
        self::LOW_BALANCE,
        self::NEFT_LIMIT_EXHAUSTED,
        self::NEFT_WINDOW_CLOSED,
    ];

    public static function validateReason(string $mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_INVALID_QUEUED_REASON,
                null,
                [
                    'mode' => $mode,
                ]);
        }
    }

    protected static function isValid(string $mode): bool
    {
        return (in_array($mode, self::$queuedReasons) === true);
    }
}
