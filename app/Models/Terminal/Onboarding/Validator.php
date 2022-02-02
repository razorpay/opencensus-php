<?php


namespace RZP\Models\Terminal\Onboarding;

use RZP\Base;
use RZP\Models\Terminal\Entity as TerminalEntity;

class Validator extends Base\Validator
{
    protected static $onboardingInputRules = [
        TerminalEntity::GATEWAY            => 'required|in:wallet_paypal,paytm,wallet_phonepe',
        TerminalEntity::IDENTIFIERS        => 'sometimes',
        TerminalEntity::SECRETS            => 'sometimes',
    ];
}
