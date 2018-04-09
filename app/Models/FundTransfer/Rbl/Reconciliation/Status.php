<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{
    const SUCCESS   = 'SUCCESS';
    const FAILURE   = 'FAILURE';
    const INITIATED = 'Initiated';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::SUCCESS,
            self::INITIATED
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::FAILURE
        ];
    }
}
