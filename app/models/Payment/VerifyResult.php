<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Merchant;
use Models\Terminal;
use Models\Payment;
use RZP\Trace\Trace;

class VerifyResult
{
    const UNKNOWN   = null;
    const FAILED    = 0;
    const SUCCESS   = 1;
    const ERROR     = 2;
}
