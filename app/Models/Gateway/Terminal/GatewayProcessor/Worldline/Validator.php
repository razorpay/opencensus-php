<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Worldline;

use RZP\Base;
use RZP\Models\Gateway\Terminal\Constants; 

class Validator extends Base\Validator
{
    protected static $gatewayInputRules = [
        Constants::MPAN                           => 'bail|required|array',
        Constants::MPAN.'.'.Constants::MASTERCARD => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::VISA       => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::RUPAY      => 'required|string|size:16',
    ];
}