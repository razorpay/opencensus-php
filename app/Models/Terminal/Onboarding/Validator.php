<?php


namespace RZP\Models\Terminal\Onboarding;

use RZP\Base;
use RZP\Models\Terminal\Entity as TerminalEntity;

class Validator extends Base\Validator
{
    protected static $onboardingInputRules = [
        TerminalEntity::GATEWAY            => 'required',
        TerminalEntity::GATEWAY_ACQUIRER   => 'sometimes',
    ];
}