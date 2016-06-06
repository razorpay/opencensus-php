<?php

namespace Gateway\Cybersource\Payment;

use Gateway\Cybersource;

final class Result
{
    /**
     * Result codes received in response for card enrollment
     */

    const ENROLLED      = 475;

    const NOT_ENROLLED  = 100;

    const CAPTURED      = 100;

    /**
     * Result codes received in response for payment
     */
    const APPROVED          = 'APPROVED';
    const SUCCESS           = 'SUCCESS';
    const NOT_CAPTURED      = 'NOT CAPTURED';
    const NOT_APPROVED      = 'NOT APPROVED';
    const DENIED_BY_RISK    = 'DENIED BY RISK';
    const HOST_TIMEOUT      = 'HOST TIMEOUT';
    const AUTH_ERROR        = 'AUTH ERROR';

    public static function getResultCode($result)
    {
        $success = true;

        switch ($result)
        {
            case 'ENROLLED':
                $result = self::ENROLLED;
                break;
            case 'NOT ENROLLED':
                $result = self::NOT_ENROLLED;
                break;
            case 'INITIALIZED':
                $result = self::INITIALIZED;
                break;
            case 'FSS0001-Authentication Not Available':
                $result = self::FSS0001_ENROLLED;
                $success = false;
                break;
            case 'AUTH ERROR':
                $result = self::AUTH_ERROR;
                $success = false;
                break;
            default:
                $result = self::UNKNOWN_ERROR_ENROLLED;
                $success = false;
        }

        return array($result, $success);
    }
}