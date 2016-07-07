<?php

namespace Gateway\Wallet\Olamoney;

use EE\Error;
use EE\Error\ErrorCode;

class Status
{
    const SUCCESS               = 'success';
    const FAILED                = 'error';
    const COMPLETED             = 'completed';
    // need to reconsider the following
    const NLOGGEDIN             = 101;
    const NOBALANCE             = 102;
    const INSUFFICIENTBALANCE   = 103;
    const HASHFAILED            = 105;
    const DUPLICATE             = 106;
}
