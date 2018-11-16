<?php

namespace RZP\Gateway\Cybersource;

class Status
{
    /*
     * The status here occurs as following:
     *
     * After sending card enroll request, we get either failure
     * (with different 'result' var), ENROLLED or NOT_ENROLLED.
     * So, store either ENROLLED or NOT_ENROLLED or ENROLL_FAILED
     */

    const AUTHORIZED       = 'authorized';
    const AUTHORIZE_FAILED = 'authorize_failed';
    const ENROLL_FAILED    = 'enroll_failed';
    const CAPTURED         = 'captured';
    const CAPTURE_FAILED   = 'capture_failed';
    const CREATED          = 'created';
    const REFUNDED         = 'refunded';
    const REFUND_FAILED    = 'refund_failed';
    const REVERSED         = 'reversed';
    const REVERSE_FAILED   = 'reverse_failed';
    const VOIDED           = 'voided';
    const VOID_FAILED      = 'void_failed';
}
