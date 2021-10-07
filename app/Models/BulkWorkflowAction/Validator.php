<?php

namespace RZP\Models\BulkWorkflowAction;

use RZP\Base;
use RZP\Models\RiskWorkflowAction\Constants as RiskActionConstants;

class Validator extends Base\Validator
{
    protected static $createDestructiveBulkRiskAttributesRules = [
        RiskActionConstants::RISK_REASON           => 'required|string|in:' . RiskActionConstants::RISK_REASONS_CSV,
        RiskActionConstants::RISK_SOURCE           => 'required|string|in:' . RiskActionConstants::RISK_SOURCES_CSV,
        RiskActionConstants::RISK_TAG              => 'sometimes|string|in:' . RiskActionConstants::RISK_TAGS_CSV,
        RiskActionConstants::TRIGGER_COMMUNICATION => 'required|string|in:0,1',
    ];

    protected static $createConstructiveBulkRiskAttributesRules = [
        RiskActionConstants::CLEAR_RISK_TAGS  => 'required|string|in:0,1',
    ];
}
