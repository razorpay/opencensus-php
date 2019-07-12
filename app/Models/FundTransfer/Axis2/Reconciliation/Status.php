<?php

namespace RZP\Models\FundTransfer\Axis2\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\Status as BaseStatus;

class Status extends BaseStatus
{

    const SUCCESS  = 'SUCCESS';
    const REJECTED = 'REJECTED';

    public static function getSuccessfulStatus(): array
    {
        return [
            self::SUCCESS => [],
        ];
    }

    public static function getFailureStatus(): array
    {
        return [
            self::REJECTED => [],
        ];
    }

    public static function getCriticalErrorStatus(): array
    {
        return [];
    }

    public static function getCriticalErrorRemarks(): array
    {
        return [
            'Debit account does not exist',
            'Beneficiary IFSC code is invalid. Unable to determine Payment type',
            'Invalid Debit A/c.',
            'ACCOUNT CLOSED',
            'NO SUCH ACCOUNT TYPE',
            'OPERATIONS SUSPENDED',
            'ACCOUNT HOLDER EXPIRED'
        ];
    }
}
