<?php

namespace RZP\Models\Payment\Verify;

use RZP\Exception;

class Result
{
    const SUCCESS       = 'success';
    const ERROR         = 'error';
    const AUTHORIZED    = 'authorized';
    const TIMEOUT       = 'timeout';
}
