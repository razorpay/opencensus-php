<?php

namespace RZP\Gateway\Cybersource;

/**
 * Raw authentication data that comes from the card-issuing bank.
 * Primary authentication field that indicates if authentication
 * was successful and if liability shift occurred.
 */
class AuthenticationResult
{
    // Successful validation
    const SUCCESSFUL        = 0;

    // Cardholder is not participating,
    // but the attempt to authenticate was recorded.
    const NOT_PARTICIPATING = 1;

    // Issuer unable to perform authentication.
    const UNABLE_TO_PERFORM = 6;

    // Cardholder did not complete authentication.
    const NOT_COMPLETED     = 9;

    // Invalid PARes.
    const INVALID_PARES     = -1;
}
