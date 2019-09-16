<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Attempt\Entity;
use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{
    const PAID                  = 'Paid';
    const CANCELLED             = 'Cancelled';
    const PENDING               = 'Pending Processing';
    const AWAITING_MESSAGING    = 'Awaiting Messaging';
    const AWAITING_LIQUIDATION  = 'Awaiting Liquidation';
    const HOLD                  = 'Hold';

    //Critical failure messages
    const RE_INITIATION                 = 'Re-Initiation';
    const FUTUREDATED_PENDINGPROCESSING = 'FutureDated/PendingProcessing';

    /**
     * These are the statuses which, if received after an attempt is marked as processed,
     * need us to mark it as initiated so that it can be reassessed by the bulk recon cron.
     *
     * @return array
     */
    public static function getFlipStatus(): array
    {
        return [
            self::CANCELLED,
            self::HOLD,
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::CANCELLED => [],
        ];
    }

    public static function getSuccessfulStatus(): array
    {
        return [
            self::PAID                  => [],
            self::PENDING               => [],
            self::AWAITING_MESSAGING    => [],
            self::AWAITING_LIQUIDATION  => [],
        ];
    }

    public static function getCriticalErrorRemarks(): array
    {
        return [
            'Rejected by RTGS Gateway',
            'Debit failed due to Insufficient Funds',
            'File rejected due to content validation fail',
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
        return [];
    }
}
