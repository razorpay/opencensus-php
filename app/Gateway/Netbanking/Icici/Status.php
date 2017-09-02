<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Models\Customer\Token;

class Status
{
    const SUCCESS     = 'SUCCESS';
    const FAILED      = 'FAILED';
    // TODO: Get exact meanings and leave comments on
    // how we get these statuses
    const REVERSED    = 'Reversed';
    const IN_PROCESS  = 'IN PROCESS';
    const ERROR       = 'Error';

    const Y           = 'Y';

    const SI_SUCCESS            = 'Success';
    const SI_FAILED             = 'Failed';
    const PAYMENT_NOT_SCHEDULED = 'NoSuchPaymentScheduled';

    const SI_FAILED_STATUSES    = [self::SI_FAILED, self::PAYMENT_NOT_SCHEDULED];

    const SI_STATUS_TO_RECURRING_STATUS_MAP = [
        'Y' => Token\RecurringStatus::CONFIRMED,
        'N' => Token\RecurringStatus::REJECTED
    ];

    public static function isSiStatusFailure(string $status)
    {
        if (in_array($status, self::SI_FAILED_STATUSES, true) === true)
        {
            return true;
        }

        return false;
    }
}
