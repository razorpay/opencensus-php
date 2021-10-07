<?php

namespace RZP\Models\Order;

class Status
{
    const CREATED       = 'created';
    const ATTEMPTED     = 'attempted';
    const PAID          = 'paid';
    const PLACED        = 'placed';

    public static function isStatusValid($status)
    {
        return (defined(Status::class.'::'.strtoupper($status)));
    }
}
