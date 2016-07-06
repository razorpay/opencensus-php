<?php

namespace Models\Payment;

use RZP\Exception;
use RZP\Error\ErrorCode;
use Models\Merchant;
use Models\Terminal;
use Models\Payment;
use Trace\Trace;

class VerifyResult
{
    const UNKNOWN   = null;
    const FAILED    = 0;
    const SUCCESS   = 1;
    const ERROR     = 2;
}
