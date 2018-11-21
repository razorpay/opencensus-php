<?php

namespace RZP\Gateway\Cybersource;

class CommerceIndicator
{
    // American Express SafeKey authentication verified successfully.
    const AESK           = 'aesk';

    // Card not enrolled in American Express SafeKey,
    // but the attempt to authenticate was recorded.
    const AESK_ATTEMPTED = 'aesk_attempted';

    // Authentication failed.
    const INTERNET       = 'internet';

    // J/Secure authentication verified successfully.
    const JS             = 'js';

    // Card not enrolled in J/ Secure,
    // but the attempt to authenticate was recorded.
    const JS_ATTEMPTED   = 'js_attempted';

    // Mail or telephone order.
    const MOTO           = 'moto';

    // Card not enrolled in Diners Club ProtectBuy,
    // but the attempt to authenticate was recorded.
    const PB_ATTEMPTED   = 'pb_attempted';

    // Recurring transaction.
    const RECURRING      = 'recurring';

    // MasterCard SecureCode authentication verified successfully.
    const SPA            = 'spa';

    // MasterCard SecureCode failed authentication.
    const SPA_FAILURE    = 'spa_failure';

    // Verified by Visa authentication verified successfully.
    const VBV            = 'vbv';

    // Card not enrolled in Verified by Visa,
    // but the attempt to authenticate was recorded.
    const VBV_ATTEMPTED  = 'vbv_attempted';

    // Verified by Visa authentication unavailable.
    const VBV_FAILURE    = 'vbv_failure';
}
