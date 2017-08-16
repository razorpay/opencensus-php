<?php

namespace RZP\Models\Payment\Verify;

use RZP\Exception;
use RZP\Error\ErrorCode;

class Action
{
    const BLOCK = 'block';
    const SKIP  = 'skip';
    const RETRY = 'retry';
}
