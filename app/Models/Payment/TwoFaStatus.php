<?php

namespace RZP\Models\Payment;

class TwoFaStatus
{
    const PASSED            = 'passed';
    const FAILED            = 'failed';
    const UNKNOWN           = 'unknown';

    // use SKIPPED when Razorpay chooses to skip 2FA
    //e.g. in case of Recurring payments
    const SKIPPED           = 'skipped';

    // use NOT_APPLICABLE for Netbanking payments
    const NOT_APPLICABLE    = 'not_applicable';

    // use UNAVAILABLE when 2FA can't be done
    // e.g. in case of internation cards that aren't enrolled in 2FA
    const UNAVAILABLE       = 'unavailable';
}