<?php

namespace RZP\Models\Plan\Subscription;

class Status
{
    // Subscription Statuses
    const CREATED           = 'created';
    const ACTIVE            = 'active';
    const PROCESSED         = 'processed';
    const ON_HOLD           = 'on_hold';
    const FAILED            = 'failed';

    // Error Statuses
    const AUTH_FAILURE      = 'auth_failure';
    const CAPTURE_FAILURE   = 'capture_failure';
}