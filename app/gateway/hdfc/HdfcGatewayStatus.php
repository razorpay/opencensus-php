<?php

namespace Gateway\HdfcGateway;

class HdfcGatewayStatus
{
    const ENROLLED = 'enrolled';

    const ENROLL_FAILED = 'enroll_failed';

    const AUTH_ENROLL_FAILED = 'auth_enroll_failed';

    const AUTH_NOT_ENROLL_FAILED = 'auth_not_enroll_failed';

    const AUTH = 'auth';

    const CAPTURED = 'captured';

    const CAPTURE_FAILED = 'capture_failed';

    const REFUND_FAILED = 'refund_failed';

    const FAILED = 'failed';
}