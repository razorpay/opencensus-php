<?php

namespace RZP\Models\Payment\Verify;

class Result
{
    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';
    const UNKNOWN       = 'unknown';
}
