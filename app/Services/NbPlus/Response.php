<?php

namespace RZP\Services\NbPlus;

class Response
{
    const RESPONSE = 'response';
    const ERROR    = 'error';
    const DATA     = 'data';
    const TOKEN    = 'token';

    // account
    const ACCOUNT_NUMBER = 'account_number';
    const BANK_IFSC      = 'bank_ifsc';

    // authorize
    const NEXT       = 'next';
    const REDIRECT   = 'redirect';
    const INTENT_URL = 'intent_url';

    const OTP_SUBMIT_URL      = 'otpSubmitUrl';

    // callback & verify
    const GATEWAY_REFERENCE_NUMBER = 'gateway_reference_number';
    const STATUS                   = 'status';
    const ACCOUNT_INFO             = 'account_info';
    const PAYMENT_ID               = 'payment_id';
    const ACCESS_TOKEN             = 'access_token';
    CONST REFRESH_TOKEN            = 'refresh_token';
    CONST ACCESS_TOKEN_EXPIRY      = 'access_token_expiry';

    // Emandate Response Fields
    const RECURRING_STATUS         = 'recurring_status';
    const GATEWAY_TOKEN            = 'gateway_token';
    const RECURRING_FAILURE_REASON = 'recurring_failure_reason';
    const BANK_REFERENCE_ID        = 'bank_reference_id';

    // verify
    const GATEWAY_STATUS = 'gateway_status';

    // debit
    const GATEWAY_PAYMENT_STATUS = 'gateway_payment_status';
}
