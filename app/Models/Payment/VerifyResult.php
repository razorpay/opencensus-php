<?php

namespace RZP\Models\Payment;

use RZP\Constants;

class VerifyResult
{
    const UNKNOWN   = Constants\Verify::VERIFIED_UNKNOWN;
    const FAILED    = Constants\Verify::VERIFIED_FAILED;
    const SUCCESS   = Constants\Verify::VERIFIED_SUCCESS;
    const ERROR     = Constants\Verify::VERIFIED_ERROR;
}
