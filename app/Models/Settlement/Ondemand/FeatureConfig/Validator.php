<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use RZP\Base;

class Validator extends Base\Validator
{
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_INPUT = 'settlement_ondemand_feature_config_input';

    const FETCH_FEATURE_CONFIG_INPUT = 'fetch_feature_config_input';

    protected static $settlementOndemandFeatureConfigInputRules = [
        Entity::MERCHANT_ID                  => 'required|string|size:14',
        Entity::PERCENTAGE_OF_BALANCE_LIMIT  => 'required|integer',
        Entity::SETTLEMENTS_COUNT_LIMIT      => 'required|integer',
        Entity::PRICING_PERCENT              => 'required|integer',
        Entity::ES_PRICING_PERCENT           => 'sometimes|integer',
        Entity::FULL_ACCESS                  => 'required|in:yes,no',
        Entity::MAX_AMOUNT_LIMIT             => 'required|integer'
    ];

    protected static $createRules = [
        Entity::MERCHANT_ID                  => 'required|string|size:14',
        Entity::PERCENTAGE_OF_BALANCE_LIMIT  => 'required|integer',
        Entity::SETTLEMENTS_COUNT_LIMIT      => 'required|integer',
        Entity::MAX_AMOUNT_LIMIT             => 'required|integer',
        Entity::PRICING_PERCENT              => 'required|integer',
        Entity::ES_PRICING_PERCENT           => 'sometimes|integer'
    ];
}
