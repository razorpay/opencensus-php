<?php

namespace RZP\Models\Merchant\Attribute;

use RZP\Models\Feature;

class IntentFeature
{
    const FEATURE_FLAG = 'feature_flag';

    const EXPECTED_VALUE = 'expected_value';

    const INTENT_FEATURE_CONF = [
        Type::CORPORATE_CARDS => [
            self::FEATURE_FLAG => Feature\Constants::CAPITAL_CARDS_ELIGIBLE,
            self::EXPECTED_VALUE => 'true',
        ],
        Type::MARKETPLACE_IS => [
            self::FEATURE_FLAG => Feature\Constants::ALLOW_ES_AMAZON,
            self::EXPECTED_VALUE => 'true',
        ],
    ];
}
