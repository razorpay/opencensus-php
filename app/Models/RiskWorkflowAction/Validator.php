<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Base;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;

class Validator extends Base\Validator
{
    protected static $createRiskActionRules                        = [
        'merchant_id'               => 'required|string|size:14',
        Constants::ACTION           => 'required|string|in:' . Constants::RISK_ACTIONS_CSV,
        Constants::RISK_ATTRIBUTES  => 'required|',
    ];

    protected static $createDestructiveRiskAttributesRules         = [
        Constants::RISK_REASON           => 'required|string|in:' . Constants::RISK_REASONS_CSV,
        Constants::RISK_SOURCE           => 'required|string|in:' . Constants::RISK_SOURCES_CSV,
        Constants::RISK_TAG              => 'sometimes|string|in:' . Constants::RISK_TAGS_CSV,
        Constants::TRIGGER_COMMUNICATION => 'required|string|in:0,1',
    ];

    protected static $createConstructiveRiskAttributesRules        = [
        Constants::CLEAR_RISK_TAGS => 'required|string|in:0,1',
    ];

    protected static $createDisableInternationalRiskAttributesRules              = [
        Constants::RISK_REASON           => 'required|string|in:' . Constants::RISK_REASONS_CSV,
        Constants::RISK_SOURCE           => 'required|string|in:' . Constants::RISK_SOURCES_CSV,
        Constants::RISK_TAG              => 'sometimes|string|in:' . Constants::RISK_TAGS_CSV,
        Constants::TRIGGER_COMMUNICATION => 'required|string|in:0,1,2',
    ];

    protected static $createEnableInternationalRiskAttributesRules = [
        ProductInternationalMapper::INTERNATIONAL_PRODUCTS => 'required|array',
        Constants::CLEAR_RISK_TAGS                         => 'sometimes|string|in:0,1',
    ];
}
