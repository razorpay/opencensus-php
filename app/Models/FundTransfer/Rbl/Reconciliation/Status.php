<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{
    const SUCCESS     = 'SUCCESS';
    const FAILURE     = 'Failure';
    const INITIATED   = 'Initiated';
    const FAILED      = 'FAILED';
    const IN_PROGRESS = 'IN PROGRESS';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::SUCCESS => [],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::FAILURE => [],
            self::FAILED  => [],
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
