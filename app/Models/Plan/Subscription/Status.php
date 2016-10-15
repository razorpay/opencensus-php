<?php

namespace RZP\Models\Plan\Subscription;

class Status
{
    const CREATED           = 'created';
    const PROCESSED         = 'processed';
    const ON_HOLD           = 'on_hold';
    const FAILED            = 'failed';

    const AUTH_FAILURE      = 'auth_failure';
}