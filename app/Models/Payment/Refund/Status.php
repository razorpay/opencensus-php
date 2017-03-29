<?php

namespace RZP\Models\Payment\Refund;

class Status
{
    const NULL      = null;
    const FULL      = 'full';
    const PARTIAL   = 'partial';

    const CREATED   = 'created';
    const PROCESSED = 'processed';
    const FAILED    = 'failed';
}
