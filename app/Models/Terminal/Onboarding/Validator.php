<?php
namespace RZP\Models\Terminal\Onboarding;
use RZP\Base;
use RZP\Models\Terminal\Onboarding\Constants as C;

class Validator extends Base\Validator
{
    protected static $freechargeInputRules = [
        Constants::MPAN                           => 'bail|required|array|min:1',
        Constants::MPAN.'.'.Constants::MASTERCARD => 'sometimes|string|size:16',
        Constants::MPAN.'.'.Constants::VISA       => 'sometimes|string|size:16',
        Constants::MPAN.'.'.Constants::RUPAY      => 'sometimes|string|size:16',
    ];
}