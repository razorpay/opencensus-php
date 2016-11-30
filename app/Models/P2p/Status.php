<?php

namespace RZP\Models\P2p;

class Status
{
    const CREATED       = 'created';
    const TRANSFERRED   = 'transferred';

    public static function isStatusValid($status)
    {
        return (defined(Status::class.'::'.strtoupper($status)));
    }
}
