<?php

namespace Models\Payment;

class Status
{
    const CREATED       = 'created';
    const AUTHORIZED    = 'authorized';
    const CAPTURED      = 'captured';
    const FAILED        = 'failed';
    const REFUNDED      = 'refunded';

    public static function isStatusValid($status)
    {
        return (defined(Status::class.'::'.strtoupper($status)));
    }
}
