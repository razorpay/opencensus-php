<?php

namespace RZP\Models\PayoutLink;

class Status
{
    const CREATED    = 'created';
    const ISSUED     = 'issued';
    const PROCESSING = 'processing';
    const SUCCESSFUL = 'successful';
    const FAILED     = 'failed';
    const CANCELLED  = 'cancelled';

}