<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

class Status
{
    // Success Status
    const SETTLED           = 'Settled';
    const EXECUTED          = 'Executed';

    // Failure status
    const REJECTED              = 'Rejected';
    const CANCELLED             = 'Cancelled';
    const PNDRETURN             = 'PndReturn';
    const RETURNED              = 'Returned';
    const RETURNAWAITED         = 'ReturnAwaited';
    const RETURNMRKDFRBULK      = 'ReturnMrkdFrBulk';
    const CHANNEL_REJECT_AUTH   = 'Channel Reject Auth';

    // Merchant level error
    const RETURNSETTLED     = 'ReturnSettled';


    public static function getSuccessfulStatus(): array
    {
        return [
            self::SETTLED,
            self::EXECUTED
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::REJECTED,
            self::CANCELLED,
            self::PNDRETURN,
            self::RETURNAWAITED,
            self::RETURNED,
            self::RETURNMRKDFRBULK,
            self::RETURNSETTLED,
            self::CHANNEL_REJECT_AUTH,
        ];
    }
}
