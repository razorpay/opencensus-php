<?php

namespace RZP\Models\BulkWorkflowAction;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createDestructiveBulkRiskAttributesRules = [
        Constants::RISK_REASON           => 'required|string|in:' . Constants::RISK_REASONS_CSV,
        Constants::RISK_SOURCE           => 'required|string|in:' . Constants::RISK_SOURCES_CSV,
        Constants::RISK_TAG              => 'sometimes|string|in:' . Constants::RISK_TAGS_CSV,
        Constants::TRIGGER_COMMUNICATION => 'required|string|in:0,1',
    ];

    protected static $createConstructiveBulkRiskAttributesRules = [
        Constants::CLEAR_RISK_TAGS  => 'required|string|in:0,1',
    ];
}
