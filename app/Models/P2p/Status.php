<?php

namespace RZP\Models\P2p;

class Status
{
    const CREATED       = 'created';
    const TRANSFERRED   = 'transferred';
    const REJECTED      = 'rejected';

    public static function isStatusValid($status)
    {
        return (defined(Status::class.'::'.strtoupper($status)));
    }
}
