<?php

namespace RZP\Models\Settlement\EarlySettlementFeaturePeriod;

use RZP\Base;

class Validator extends Base\Validator
{
   const EARLY_SETTLEMENT_FEATURE_PERIOD_INPUT = 'early_settlement_feature_period_input';

    protected static $earlySettlementFeaturePeriodInputRules = [
        Entity::MERCHANT_ID                  => 'required|string|size:14',
        Entity::FULL_ACCESS                  => 'required|in:yes,no',
        Entity::AMOUNT_LIMIT                 => 'required|integer',
        Entity::ES_PRICING                   => 'required|integer',
        Entity::DISABLE_DATE                 => 'required|string',
     ];

    protected static $createRules = [
        Entity::MERCHANT_ID                  => 'required|string|size:14',
        Entity::ENABLE_DATE                  => 'required|integer',
        Entity::DISABLE_DATE                 => 'required|integer',
        Entity::FEATURE                      => 'required|string',
        Entity::INITIAL_SCHEDULE_ID          => 'required|string|size:14',
        Entity::INITIAL_ONDEMAND_PRICING     => 'required|integer',
     ];

}
