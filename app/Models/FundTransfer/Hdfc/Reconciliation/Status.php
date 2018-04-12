<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;
use RZP\Models\FundTransfer\Hdfc\Headings;

class Status extends BaseStatus
{
    /**
     * Status : Executed
     */
    const SETTLED       = 'E';

    /**
     * Status : Rejected
     */
    const CANCELLED     = 'R';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::SETTLED
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::CANCELLED,
        ];
    }
}
