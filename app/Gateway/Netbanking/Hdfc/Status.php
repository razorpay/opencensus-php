<?php

namespace RZP\Gateway\Netbanking\Hdfc;

class Status
{
    const DEBIT_SUCCESS = 'process';
    const DEBIT_REJECT  = 'reject';

    const REGISTRATION_SUCCESS = 'success';
    const REGISTRATION_FAILURE = 'reject';

    public static function isRegistrationSuccess($status)
    {
        $status = strtolower($status);

        return ($status === self::REGISTRATION_SUCCESS);
    }

    public static function isDebitSuccess($status)
    {
        $status = strtolower($status);

        return ($status === self::DEBIT_SUCCESS);
    }
}
