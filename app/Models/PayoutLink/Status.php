<?php

namespace RZP\Models\PayoutLink;

class Status
{
    const ISSUED     = 'issued';
    const PROCESSING = 'processing';
    const PAID       = 'paid';
    const FAILED     = 'failed';
    const CANCELLED  = 'cancelled';
}
