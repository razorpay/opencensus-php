<?php

namespace RZP\Models\Payment;

class TwoFaStatus
{
    const PASSED  = 'passed';
    const FAILED  = 'failed';
    const UNKNOWN = 'unknown';
    const SKIPPED = 'skipped';
}