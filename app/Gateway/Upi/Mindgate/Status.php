<?php

namespace RZP\Gateway\Upi\Mindgate;

class Status
{
    /**
     * @see https://drive.google.com/a/razorpay.com/file/d/0B1MTSXtR53PfSFp3OHduYUhQV0U/view?usp=sharing
     */

    const SUCCESS = 'SUCCESS';

    // Only in case of payment
    const PENDING = 'PENDING';

    // Only in case of payment
    const EXPIRED = 'EXPIRED';

    const TIMEOUT = 'TIMEOUT';

    const FAILURE = 'FAILURE';

    const REFUND_SUCCESS = 'SUCCESS';

    const REFUND_FAILED = 'FAILED';

    // Status in validate VPA
    const VPA_AVAILABLE = 'VE';

    const VPA_NOT_AVAILABLE = 'VN';

    const FAILED = 'F';

    // Recurring Status
    const UPDATE        = 'UPDATE';

    const REVOKE        = 'REVOKE';

    const REVOKED       = 'REVOKED';

    const PAUSE         = 'PAUSE';

    const UNPAUSE       = 'UNPAUSE';

    const REJECTED      = 'REJECTED';

    const ACTIVE        = 'ACTIVE';

    const COMPLETED     = 'COMPLETED';
}
