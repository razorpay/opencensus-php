<?php

namespace Gateway\Hdfc;

class Status
{
    /*
     * The status here occurs as following:
     *
     * After sending card enroll request, we get either failure
     * (with different 'result' var), ENROLLED or NOT_ENROLLED.
     * So, store either ENROLLED or NOT_ENROLLED or ENROLL_FAILED
     */

    const ENROLLED = 'enrolled';

    const NOT_ENROLLED = 'not_enrolled';

    const ENROLL_FAILED = 'enroll_failed';

    const AUTH_ENROLL_FAILED = 'auth_enroll_failed';

    const AUTH_NOT_ENROLL_FAILED = 'auth_not_enroll_failed';

    const AUTHORIZED = 'authorized';

    const CAPTURED = 'captured';

    const CAPTURE_FAILED = 'capture_failed';

    const REFUND_FAILED = 'refund_failed';

    const REFUNDED = 'refunded';
}