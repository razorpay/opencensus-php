<?php

namespace RZP\Models\Payment\Downtime;

class Status
{
    const SCHEDULED = 'scheduled';
    const STARTED   = 'started';
    const UPDATED   = 'updated';
    const RESOLVED  = 'resolved';
    const CANCELLED = 'cancelled';
}
