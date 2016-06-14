<?php

namespace Gateway\Wallet\Olamoney;

use EE\Error;
use EE\Error\ErrorCode;

class Status
{
    const SUCCESS               = 100;
    const NLOGGEDIN             = 101;
    const NOBALANCE             = 102;
    const INSUFFICIENTBALANCE   = 103;
    const FAILED                = 103;
    const HASHFAILED            = 105;
    const DUPLICATE             = 106;
}
