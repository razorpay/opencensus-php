<?php

namespace RZP\Models\PayoutLink;

class Status
{
    const ISSUED     = 'issued';
    const PAID       = 'paid';
    const FAILED     = 'failed';
    const CANCELLED  = 'cancelled';
    const PROCESSING = 'processing';
}