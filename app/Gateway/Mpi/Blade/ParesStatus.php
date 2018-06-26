<?php

namespace RZP\Gateway\Mpi\Blade;

class ParesStatus
{
    /**
     * Length: 1 character
     * Value: must be one of the following:
     * – Y = Authentication Successful.
     * Customer was successfully authenticated.
     * All data needed for clearing, including the
     * Cardholder Authentication Verification Value,
     * is included in the message.
     *
     * – N = Authentication Failed
     * Customer failed or cancelled
     * authentication. Transaction
     * denied.
     *
     * – U = Authentication Could Not Be Performed.
     * Authentication could not be completed,
     * due to technical or other problem,
     * as indicated in PARes.IReq.
     *
     * – A = Attempts Processing
     * Performed. Authentication could
     * not be completed, but a proof of
     * authentication attempt (CAVV)
     * was generated.
     */

    const Y = 'Y';
    const N = 'N';
    const U = 'U';
    const A = 'A';

    protected static $authenticateStatusMap = [
        self::Y => AuthenticationStatus::Y,
        self::N => AuthenticationStatus::F,
        self::U => AuthenticationStatus::U,
        self::A => AuthenticationStatus::U,
    ];

    public static function getAuthenticationStatus(string $paresStatus)
    {
        return self::$authenticateStatusMap[$paresStatus];
    }
}
