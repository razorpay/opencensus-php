<?php

namespace RZP\Models\TerminalOnboardingDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $verifyTerminalRules = [
        'count'                     => 'sometimes|integer|min:1',
    ];
}
