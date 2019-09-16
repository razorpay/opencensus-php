<?php

namespace RZP\Gateway\Netbanking\Icici;

/* This class is used in 2 scenarios
 * 1. While sending the auth request, it is used to specify
 *    whether bank payment id is needed in response
 * 2. Callback response contains Y or N as status of the payment
 */
class Confirmation
{
    // We use Y to generate bank_payment_id in callback response
    const YES     = 'Y';
    const NO      = 'N';
    const PENDING = 'P';

    public static function getAuthSuccessStatus()
    {
        return self::YES;
    }
}
