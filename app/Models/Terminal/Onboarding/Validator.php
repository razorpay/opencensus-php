<?php


namespace RZP\Models\Terminal\Onboarding;

use RZP\Base;

class Validator extends Base\Validator
{
    const GATEWAY = 'gateway';

    protected static $onboardingInputRules = [
        self::GATEWAY       => 'required',
    ];
}