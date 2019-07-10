<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{
    const PROCESSED = 'P';
    const CANCELLED = 'C';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::PROCESSED => [],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::CANCELLED => [],
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
        return [];
    }

    public static function getCriticalErrorRemarks(): array
    {
        return [];
    }
}
