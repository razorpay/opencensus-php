<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class Status
{
    const SUCCESS = 'Y';

    public static function isSuccess($status): bool
    {
        return ($status === Status::SUCCESS);
    }
}
