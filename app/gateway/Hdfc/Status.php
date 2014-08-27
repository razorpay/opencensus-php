<?php

namespace Gateway\Hdfc;

class Status
{
    const ENROLLED = 'enrolled';

    const ENROLL_FAILED = 'enroll_failed';

    const AUTH_ENROLL_FAILED = 'auth_enroll_failed';

    const AUTH_NOT_ENROLL_FAILED = 'auth_not_enroll_failed';

    const AUTHORIZED = 'authorized';

    const CAPTURED = 'captured';

    const CAPTURE_FAILED = 'capture_failed';

    const REFUND_FAILED = 'refund_failed';

    const FAILED = 'failed';
}