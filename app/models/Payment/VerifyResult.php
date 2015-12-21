<?php

namespace Models\Payment;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Merchant;
use Models\Terminal;
use Models\Payment;
use Trace\Trace;

class VerifyResult
{
    const UNKNOWN   = null;
    const SUCCESS   = 0;
    const FAILED    = 1;
    const ERROR     = 2;
}
