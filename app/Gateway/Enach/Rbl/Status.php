<?php

namespace RZP\Gateway\Enach\Rbl;

class Status
{
    const DEBIT_SUCCESS = '';
    const DEBIT_REJECT  = 'REJECT';

    const ACKNOWLEDGE_SUCCESS = 'true';
    const ACKNOWLEDGE_FAILURE = 'false';

    const REGISTRATION_SUCCESS = 'active';
    const REGISTRATION_FAILURE = '';

    public static function isAcknowledgeSuccess($status)
    {
        $status = strtolower($status);

        return ($status === self::ACKNOWLEDGE_SUCCESS);
    }

    public static function isRegistrationSuccess($status)
    {
        $status = strtolower($status);

        return ($status === self::REGISTRATION_SUCCESS);
    }

    public static function isDebitSuccess($status)
    {
        $status = strtolower($status);

        return ($status !== self::DEBIT_REJECT);
    }
}
