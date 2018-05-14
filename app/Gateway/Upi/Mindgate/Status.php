<?php

namespace RZP\Gateway\Upi\Mindgate;

class Status
{
    /**
     * @see https://drive.google.com/a/razorpay.com/file/d/0B1MTSXtR53PfSFp3OHduYUhQV0U/view?usp=sharing
     */

    const SUCCESS = 'SUCCESS';

    const PENDING = 'PENDING';

    const FAILURE = 'FAILURE';

    const TIMEOUT = 'TIMEOUT';

    const REFUND_SUCCESS = 'SUCCESS';

    const REFUND_FAILED = 'FAILED';

    const VPA_AVAILABLE = 'VE';

    const VPA_NOT_AVAILABLE = 'VN';

    const FAILED = 'F';
}
