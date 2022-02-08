<?php

namespace RZP\Models\Merchant\Escalations\Actions\Handlers;

class Constants
{
    const SOFT_LIMIT = 'soft_limit';
    const HARD_LIMIT = 'hard_limit';

    // available escalation types
    const ESCALATION_TYPES = [
        self::SOFT_LIMIT,
        self::HARD_LIMIT
    ];

    const EMAIL         = 'email';
    const WORKFLOW      = 'workflow';
    const ESCALATION_V2 = 'ESCALATION_V2';

    //available milestones
    const HARD_LIMIT_LEVEL_4 = 'hard_limit_level_4';

    const TYPE  = 'type';
    const LEVEL = 'level';

    const MILESTONE_MAPPING = [
        self::HARD_LIMIT_LEVEL_4 => [
            self::TYPE  => self::HARD_LIMIT,
            self::LEVEL => 4
        ]
    ];

    const HOLD_FUNDS_REASON_FOR_NO_DOC_LIMIT_BREACH = 'GMV hard limit for no-doc onboarding breached for the merchant.';
}
