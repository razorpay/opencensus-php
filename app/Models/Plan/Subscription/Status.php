<?php

namespace RZP\Models\Plan\Subscription;

class Status
{
    const CREATED           = 'created';
    const PROCESSED         = 'processed';
    const FAILED            = 'failed';


    const AUTHORIZED        = 'authorized';
    const CAPTURE_FAILED    = 'capture_failed';
    const RETRY             = 'retry';
}