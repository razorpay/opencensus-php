<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Models\Pricing;
use RZP\Models\Admin\Org;
use RZP\Models\Schedule\Period;

class Validator extends Base\Validator
{
    const EVENT_PROMOTION = 'event_promotion';

    const DEACTIVATE                 = 'deactivate';

    protected static $createRules = [
        Entity::NAME                    => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-\s]+/|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:0',
        Entity::CREDIT_TYPE             => 'required|in:amount,reward_fee',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer|min:1',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
        Entity::PRICING_PLAN_ID         => 'sometimes|alpha_num|size:14|custom',
        Entity::PURPOSE                 => 'required|string|max:50',
        Entity::CREATOR_NAME            => 'required|string|max:50',
        Entity::CREATOR_EMAIL           => 'required|string',
        Entity::START_AT                => 'sometimes|epoch',
        Entity::END_AT                  => 'sometimes|epoch',
        Entity::PRODUCT                 => 'sometimes|string|in:banking,primary'
    ];

    protected static $eventPromotionRules = [
        Entity::NAME                    => 'required|string|regex:/^[a-zA-Z0-9][a-zA-Z0-9-\s]+/|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:0|max:500000000',
        Entity::CREDIT_TYPE             => 'sometimes|in:reward_fee',
        Entity::EVENT_ID                => 'required|string|size:14',
        Entity::PURPOSE                 => 'required|string|max:50',
        Entity::START_AT                => 'sometimes|epoch',
        Entity::END_AT                  => 'sometimes|epoch',
        Entity::PRODUCT                 => 'required|string|in:banking',
    ];

    protected static $editRules = [
        Entity::NAME                    => 'sometimes|string|max:50',
        Entity::CREDIT_AMOUNT           => 'sometimes|integer|min:0',
        Entity::CREDIT_TYPE             => 'sometimes|in:amount',
        Entity::ITERATIONS              => 'sometimes|integer|min:1',
        Entity::CREDITS_EXPIRE          => 'sometimes|integer|in:0,1',
        Entity::CREDITS_EXPIRY_INTERVAL => 'required_if:credits_expire,1|integer',
        Entity::CREDITS_EXPIRY_PERIOD   => 'required_if:credits_expire,1|string|custom',
        Entity::STATUS                  => 'sometimes|in:deactivated',
        Entity::DEACTIVATED_BY          => 'required_if:status,deactivated|string',
    ];

    protected static $eventPromotionValidators = [
        Entity::START_AT,
    ];

    protected static $deactivateRules = [
        Entity::DEACTIVATED_BY          => 'required|string',
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

    protected function validateStartAt(array $input)
    {
        $currentTimestamp = Carbon::now()->getTimestamp();

        if ((isset($input[Entity::START_AT]) === true) and
             (isset($input[Entity::END_AT]) === true))
        {
            $startAt = $input[Entity::START_AT];

            $endAt = $input[Entity::END_AT];

            if ($startAt > $endAt)
            {
                throw new  Exception\BadRequestValidationFailureException(
                    'The promotions start date should be less than end date ',
                    null,
                    [
                        'start_at' => $startAt,
                        'end_at'   => $endAt,
                    ]);
            }

            if ($endAt < $currentTimestamp)
            {
                throw new  Exception\BadRequestValidationFailureException(
                    'The promotions can be created for only future dates ',
                    null,
                    [
                        'start_at' => $startAt,
                        'end_at'   => $endAt,
                    ]);
            }
            if ($startAt === $endAt)
            {
                throw new  Exception\BadRequestValidationFailureException(
                    'The promotions cannot have same start and end time ',
                    null,
                    [
                        'start_at' => $startAt,
                        'end_at'   => $endAt,
                    ]);
            }
        }

        else if (isset($input[Entity::START_AT]) === true)
        {
            $startAt = $input[Entity::START_AT];

            if ($startAt < $currentTimestamp)
            {
                throw new  Exception\BadRequestValidationFailureException(
                    'The promotions can be created for only future dates ',
                    null,
                    [
                        'start_at' => $startAt,
                    ]);
            }
        }

        else if (isset($input[Entity::END_AT]) === true)
        {
            $endAt = $input[Entity::END_AT];

            if ($endAt < $currentTimestamp)
            {
                throw new  Exception\BadRequestValidationFailureException(
                    'The promotions can be created for only future dates ',
                    null,
                    [
                        'end_at'   => $endAt,
                    ]);
            }
        }
    }
}
