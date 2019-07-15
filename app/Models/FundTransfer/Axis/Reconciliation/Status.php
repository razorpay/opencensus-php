<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
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
    const RETURNSETTLED         = 'ReturnSettled';

    // Failure Remarks
    const NOFUNDSAVAILABLE        = 'NOFUNDSAVAILABLE';
    const NO_FUNDS_AVAILABLE      = 'No Funds available';
    const BANKIDENTIFIERINCORRECT = 'BankIdentifierIncorrect';


    public static function getSuccessfulStatus(): array
    {
        return [
            self::SETTLED  => [],
            self::EXECUTED => [],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::REJECTED             => [],
            self::CANCELLED            => [],
            self::PNDRETURN            => [],
            self::RETURNAWAITED        => [],
            self::RETURNED             => [],
            self::RETURNMRKDFRBULK     => [],
            self::RETURNSETTLED        => [],
            self::CHANNEL_REJECT_AUTH  => [],
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
        return [];
    }

    public static function getCriticalErrorRemarks(): array
    {
        return [
            self::NOFUNDSAVAILABLE,
            self::NO_FUNDS_AVAILABLE,
            self::BANKIDENTIFIERINCORRECT
        ];
    }

    /**
     * These are the statuses which, if received after an attempt is marked as processed,
     * need us to mark it as initiated so that it can be reassessed by the bulk recon cron.
     *
     * @return array
     */
    public static function getFlipStatus(): array
    {
        return [
            self::RETURNSETTLED,
        ];
    }
}
