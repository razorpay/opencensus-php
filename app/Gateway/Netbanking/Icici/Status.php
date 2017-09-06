<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Models\Customer\Token;

class Status
{
    /**
     * This status appears in verify when the payment is successful on the bank's end
     */
    const SUCCESS     = 'SUCCESS';

    /**
     * This status appears in verify when the payment is failed on the bank's end
     */
    const FAILED      = 'FAILED';

    /**
     * This status appears in verify when the payment has been reversed by the bank
     */
    const REVERSED    = 'Reversed';

    /**
     * This status appears in verify when the payment is still in process
     */
    const IN_PROCESS  = 'IN PROCESS';

    /**
     * This status appears when there's an error in the payment, and is similar to a failure
     */
    const ERROR       = 'Error';

    const Y           = 'Y';
    const N           = 'N';

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
