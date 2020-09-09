<?php

namespace RZP\Models\SubscriptionRegistration;

class SubscriptionRegistrationConstants
{
    const SUCCESS          = 'success';
    const ERRORS           = 'errors';
    const PAYMENT_RESPONSE = 'payment_response';
    const URL              = 'url';

    //
    // auth link status
    // indicates payment is pending from bank
    //
    const PENDING = 'pending';

    /**
     * Banks on which we can charge while doing Mandate Registration
     * Exact bank names here as invoice/subscription entity does not return bank code
     */
    const banksForDebitOnMandateRegister    = [ 'ICICI Bank', 'HDFC Bank' ];
    const authTypeForDebitOnMandateRegister = [ 'netbanking' ];
}
