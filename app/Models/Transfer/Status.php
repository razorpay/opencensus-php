<?php

namespace RZP\Models\Transfer;

class Status
{
    const CREATED                   = 'created';
    const PROCESSED                 = 'processed';
    const FAILED                    = 'failed';
    const REVERSED                  = 'reversed';
    const PARTIALLY_REVERSED        = 'partially_reversed';

    public static function isStatusValid($status)
    {
        return (defined(__CLASS__ . '::' . strtolower($status)));
    }
}
