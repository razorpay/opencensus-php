<?php

namespace RZP\Gateway\Wallet\Mpesa;

class StatusCode
{
    const SUCCESS = '100';
    const FAILURE = '101';

    public static function checkIfSuccessStatus($status)
    {
        return ($status === self::SUCCESS);
    }
}
