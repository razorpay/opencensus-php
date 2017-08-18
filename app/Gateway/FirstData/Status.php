<?php

namespace RZP\Gateway\FirstData;

class Status
{
    const APPROVED   = 'APPROVED';
    const AUTHORIZED = 'AUTHORIZED';
    const CAPTURED   = 'CAPTURED';
    const SETTLED    = 'SETTLED';
    const FAILED     = 'FAILED';
    const VOIDED     = 'VOIDED';
    const WAITING    = 'WAITING_3D_SECURE';

    // Voided is not actually a valid state for a credit transaction
    // However, this is being used for verify refund flow, where, if
    // a payment has been reversed, we are actually checking the
    // original preauth transaction and not a credit transaction.
    //
    // If that transaction is voided, refund was successful.
    const VALID_REFUND_STATES = [
        self::SETTLED,
        self::CAPTURED,
        self::VOIDED,
    ];
}
