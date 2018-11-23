<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class Status
{
    const SUCCESS = 'S';

    //const FAILURE = 'N'; have to verify

    public static function isSuccess($status): bool
    {
        return ($status === Status::SUCCESS);
    }
}
