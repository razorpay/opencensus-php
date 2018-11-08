<?php

namespace RZP\Models\Payment;

class TwoFactorAuth
{
    // use PASSED when a payment, that went through 2FA, succeeds
    const PASSED            = 'passed';

    // use NOT_APPLICABLE when 2FA can't be done
    // e.g. in case of international cards that aren't enrolled in 2FA
    const NOT_APPLICABLE    = 'not_applicable';

    // use UNAVAILABLE for Netbanking payments
    const UNAVAILABLE       = 'unavailable';

    // use SKIPPED when Razorpay chooses to skip 2FA
    // e.g. in case of Recurring payments
    const SKIPPED           = 'skipped';

    // use FAILED when on payment failure, the gateway error code indicates 2FA failure
    const FAILED            = 'failed';

    // use UNKNOWN when the gateway error code on payment failure doesn't clearly
    // indicate whether it failed because of 2FA failure
    const UNKNOWN           = 'unknown';
}
