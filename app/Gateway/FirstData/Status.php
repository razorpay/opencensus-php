<?php

namespace RZP\Gateway\FirstData;

class Status
{
    const FAILED                    = 'FAILED';
    const VOIDED                    = 'VOIDED';
    const SETTLED                   = 'SETTLED';
    const WAITING                   = 'WAITING';
    const APPROVED                  = 'APPROVED';
    const CAPTURED                  = 'CAPTURED';
    const AUTHORIZED                = 'AUTHORIZED';
    const WAITING_3DS               = 'WAITING_3D_SECURE';
    const WAITING_3DS_IN_ENROLL     = '?:waiting 3dsecure';

    // Used for verify payment flow. The verify response usually contains
    // either AUTHORIZED or CAPTURED to indicate a successful payment.
    //
    // However, for Rupay and Maestro cards (which use sale)
    // this is changed to SETTLED after a few days.
    //
    const SUCCESSFUL_AUTH_STATES = [
        self::AUTHORIZED,
        self::CAPTURED,
        self::SETTLED,
    ];

    // Voided is not actually a valid state for a credit transaction
    // However, this is being used for verify refund flow, where, if
    // a payment has been reversed, we are actually checking the
    // original preauth transaction and not a credit transaction.
    //
    // If that transaction is voided, refund was successful.
    const SUCCESSFUL_REFUND_STATES = [
        self::SETTLED,
        self::CAPTURED,
        self::VOIDED,
    ];

    // Yes, FirstData seriously has more than one of these.
    //
    // The latter was observed for sale txns, might have a
    // different meaning than waiting for issuing bank.
    const WAITING_STATES = [
        self::WAITING_3DS,
        self::WAITING,
    ];
}
