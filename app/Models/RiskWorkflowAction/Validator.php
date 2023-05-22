<?php

namespace RZP\Models\RiskWorkflowAction;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant\ProductInternational\ProductInternationalMapper;

class Validator extends Base\Validator
{
    protected static $createRiskActionRules                        = [
        'merchant_id'               => 'required|string|size:14',
        Constants::ACTION           => 'required|string|in:' . Constants::RISK_ACTIONS_CSV,
        Constants::RISK_ATTRIBUTES  => 'required|',
    ];

    protected static $createRiskActionInternalRules                    = [
        'merchant_id'               => 'required|string|size:14',
        Constants::ACTION           => 'required|string|in:' . Constants::RISK_ACTIONS_CSV,
        Constants::RISK_ATTRIBUTES  => 'required|',
        Constants::MAKER_ADMIN_ID   => 'required|string|size:20'
    ];

    protected static $createRiskActionRasRules                    = [
        'merchant_id'                   => 'required|string|size:14',
        Constants::ACTION               => 'required|string|in:' . Constants::RISK_ACTIONS_CSV,
        Constants::RISK_ATTRIBUTES      => 'required|',
        Constants::RISK_WORKFLOW_TAGS   => 'required|',
    ];

    protected static $createDestructiveRiskAttributesRules         = [
        Constants::RISK_REASON           => 'required|string',
        Constants::RISK_SUB_REASON       => 'required|string',
        Constants::RISK_SOURCE           => 'required|string|in:' . Constants::RISK_SOURCES_CSV,
        Constants::RISK_TAG              => 'sometimes|string|in:' . Constants::RISK_TAGS_CSV,
        Constants::TRIGGER_COMMUNICATION => 'required|string|in:0,1',
    ];

    protected static $createConstructiveRiskAttributesRules        = [
        Constants::CLEAR_RISK_TAGS => 'required|string|in:0,1',
    ];

    protected static $createDisableInternationalRiskAttributesRules              = [
        Constants::RISK_REASON           => 'required|string',
        Constants::RISK_SUB_REASON       => 'required|string',
        Constants::RISK_SOURCE           => 'required|string|in:' . Constants::RISK_SOURCES_CSV,
        Constants::RISK_TAG              => 'sometimes|string|in:' . Constants::RISK_TAG_INTERNATIONAL_DISABLEMENT,
        Constants::TRIGGER_COMMUNICATION => 'required|string|in:0,1,2',
    ];

    protected static $createEnableInternationalRiskAttributesRules = [
        ProductInternationalMapper::INTERNATIONAL_PRODUCTS => 'required|array',
    ];

    public function validateRiskReasonAndSubReason($riskReason, $riskSubReason)
    {
        if (in_array($riskReason, array_keys(Constants::RISK_REASONS_MAP)) === false)
        {
            throw new Exception\BadRequestValidationFailureException($riskReason . ' is not a valid risk reason');
        }

        $riskSubReasons = Constants::RISK_REASONS_MAP[$riskReason] ?? [];

        if (in_array($riskSubReason, $riskSubReasons) === false)
        {
            throw new Exception\BadRequestValidationFailureException($riskSubReason .
             ' is not a valid risk sub-reason for the following risk reason: ' . $riskReason);
        }
    }
}
