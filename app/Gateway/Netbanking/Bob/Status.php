<?php

namespace RZP\Gateway\Netbanking\Bob;

class Status
{
    const SUCCESS = 'S';
    const FAILURE = 'F';

    public static function isSuccess($status): bool
    {
        return ($status === Status::SUCCESS);
    }
}
