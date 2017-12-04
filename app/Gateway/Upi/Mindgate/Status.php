<?php

namespace RZP\Gateway\Upi\Mindgate;

class Status
{
    /**
     * @see https://drive.google.com/a/razorpay.com/file/d/0B1MTSXtR53PfSFp3OHduYUhQV0U/view?usp=sharing
     */

    const SUCCESS = 'Success';

    const PENDING = 'Pending';

    const FAILURE = 'Failure';

    const TIMEOUT = 'Timeout';

    const REFUND_SUCCESS = 'S';

    const VPA_AVAILABLE = 'VE';

    const VPA_NOT_AVAILABLE = 'VN';

    const FAILED = 'F';
}
