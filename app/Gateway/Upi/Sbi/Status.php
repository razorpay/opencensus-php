<?php

namespace RZP\Gateway\Upi\Sbi;

class Status
{
    const SUCCESS = 'P';

    public static function isStatusSuccess(string $status)
    {
        return $status === self::SUCCESS;
    }
}