<?php

namespace RZP\Models\Terminal\Onboarding;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $freechargeInputRules = [
        Constants::MPAN                           => 'bail|required|array',
        Constants::MPAN.'.'.Constants::MASTERCARD => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::VISA       => 'required|string|size:16',
        Constants::MPAN.'.'.Constants::RUPAY      => 'required|string|size:16',
    ];
}