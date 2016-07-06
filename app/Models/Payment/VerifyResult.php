<?php

namespace RZP\Models\Payment;

use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Trace\Trace;

class VerifyResult
{
    const UNKNOWN   = null;
    const FAILED    = 0;
    const SUCCESS   = 1;
    const ERROR     = 2;
}
