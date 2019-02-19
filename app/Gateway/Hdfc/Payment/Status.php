<?php

namespace RZP\Gateway\Hdfc\Payment;

class Status
{
    /*
     * The status here occurs as following:
     *
     * After sending card enroll request, we get either failure
     * (with different 'result' var), ENROLLED or NOT_ENROLLED.
     * So, store either ENROLLED or NOT_ENROLLED or ENROLL_FAILED
     */

    const INITIALIZED                         = 'initialized';
    const ENROLLED                            = 'enrolled';
    const NOT_ENROLLED                        = 'not_enrolled';
    const ENROLL_FAILED                       = 'enroll_failed';
    const AUTH_ENROLL_FAILED                  = 'auth_enroll_failed';
    const AUTH_NOT_ENROLL_FAILED              = 'auth_not_enroll_failed';
    const AUTH_RECURRING_FAILED               = 'auth_recurring_failed';
    const AUTHORIZED                          = 'authorized';
    const AUTHORIZE_FAILED                    = 'authorize_failed';
    const CAPTURED                            = 'captured';
    const CAPTURE_FAILED                      = 'capture_failed';
    const REFUND_FAILED                       = 'refund_failed';
    const REFUNDED                            = 'refunded';
    const INITIALIZED_DEBIT_PIN               = 'INITIALIZED';
    const DEBIT_PIN_AUTHENTICATION_FAILED     = 'debit_pin_authentication_failed';
    const DEBIT_PIN_AUTHORIZATION_FAILED      = 'debit_pin_authorization_failed';

    public static function getSuccessStatusArray()
    {
        return [self::AUTHORIZED, self::CAPTURED];
    }
}
