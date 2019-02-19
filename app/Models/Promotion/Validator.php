<?php

namespace RZP\Models\Promotion;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Models\Admin\Org;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|alpha_dash|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:0',
        Entity::CREDIT_TYPE             => 'required|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer|min:1',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
        Entity::PRICING_PLAN_ID         => 'sometimes|alpha_num|size:14|custom',
        Entity::PURPOSE                 => 'required|string|max:50',
        Entity::CREATOR_NAME            => 'required|string|max:50',
    ];

    protected static $editRules = [
        Entity::NAME                    => 'sometimes|alpha_dash|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:0',
        Entity::CREDIT_TYPE             => 'sometimes|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
    ];

    protected  function validatePricingPlanId($attribute, $value)
    {
        $orgId = app('basicauth')->getOrgId();

        $orgId =  Org\Entity::verifyIdAndStripSign($orgId);

        (new Pricing\Repository)->getPricingPlanByIdAndOrgId($value,$orgId);
    }

    protected function validateCreditsExpiryPeriod($attribute, $value)
    {
        $validPeriods = [
            Period::HOURLY,
            Period::DAILY,
            Period::MONTHLY,
            Period::WEEKLY,
        ];

        if (in_array($value, $validPeriods, true) === false)
        {
            throw new  Exception\BadRequestValidationFailureException(
                'The credits expiry period is not valid',
                $attribute,
                $value);
        }
    }
}
