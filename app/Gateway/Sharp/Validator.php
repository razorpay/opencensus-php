<?php

namespace RZP\Gateway\Sharp;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $testBharatqrPaymentRules = [
        Fields::METHOD    => 'required|in:card,upi',
        Fields::AMOUNT    => 'required|integer|min:0',
        Fields::REFERENCE => 'required|string|max:18',
    ];

    protected static $preDebitRules = [
        'action'            => 'required|string',
        'gateway'           => 'required|string',
        'terminal'          => 'required',
        'payment'           => 'required',
        'upi_mandate'       => 'required',
        'upi'               => 'required',
        'merchant'          => 'required',
    ];
}
