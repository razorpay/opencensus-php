<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

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

    /**
     * Internal status used when file level failure occurred.
     * It is considered as failed status
     */
    const FILE_ERROR    = 'file_error';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::SETTLED => [],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::CANCELLED  => [],
            self::FILE_ERROR => [],
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
        return [
            self::FILE_ERROR => [],
        ];
    }

    public static function getCriticalErrorRemarks(): array
    {
        return [];
    }
}
