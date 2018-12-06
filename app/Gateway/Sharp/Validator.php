<?php

namespace RZP\Gateway\Sharp;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $testBharatqrPaymentRules = [
        Fields::METHOD    => 'required|in:card,upi',
        Fields::AMOUNT    => 'required|integer|min:0',
        Fields::REFERENCE => 'required|string|size:17',
    ];
}
