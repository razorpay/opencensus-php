<?php

namespace RZP\Models\Payment\Verify;

class Result
{
    const SUCCESS         = 'success';
    const ERROR           = 'error';
    const REQUEST_ERROR   = 'request_error';
    const AUTHORIZED      = 'authorized';
    const TIMEOUT         = 'timeout';
    const UNKNOWN         = 'unknown';
    const REARCH_CAPTURED = 'rearch_captured';
}
