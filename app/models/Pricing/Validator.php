<?php

namespace Models\Pricing;

use EE\Exception;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY             => 'sometimes|in:hdfc',
        Entity::PAYMENT_MODE        => 'required|alpha|in:card',
        Entity::PAYMENT_MODE_TYPE   => 'required_if:payment_mode,card|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'sometimes|alpha|in:VISA,MC,DICL,RP,MAES',
        Entity::PAYMENT_ISSUER      => 'sometimes|alpha|max:10',
        Entity::PERCENT_RATE        => 'sometimes|numeric|max:10000',
        Entity::FIXED_RATE          => 'sometimes|numeric|max:100000');

    protected static $addPlanRuleValidators = array('addPlanRuleExtras');

    protected static $addPlanRules = array(
        Entity::PLAN_NAME => 'required|alpha|max:20');

    protected function validateAddPlanRuleExtras($input)
    {
        if ((isset($input[Entity::PERCENT_RATE]) === false) and
            (isset($input[Entity::FIXED_RATE]) === false))
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_RATE_NOT_DEFINED);
        }
    }

    public static function createPlanValidate($input)
    {
        $instance = new static;

        $planInput[Entity::PLAN_NAME] =
            (isset($input[Entity::PLAN_NAME])) ? $input[Entity::PLAN_NAME] : null;

        $instance->validateInput('addPlan', $planInput);

        unset($input[Entity::PLAN_NAME]);

        $instance->validateInput('addPlanRule', $input);
    }

    public static function addPlanRuleValidate(Plan $plan, $input)
    {
        $instance = new static;

        $instance->validateInput('addPlanRule', $input);

        $rule = $plan->first();

        $instance->matchGateway($rule, $input);

        $instance->matchPaymentRules($plan, $input);
    }

    protected function matchGateway($planRule, $input)
    {
        $gateway = $planRule->getGateway();

        if ($gateway !== null)
        {
            if ((isset($input[Entity::GATEWAY]) === false) or
                ($input[Entity::GATEWAY] !== $gateway))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_GATEWAY_REQUIRED);
            }
        }
    }

    public function matchPaymentRules($plan, $input)
    {
        $rules = $plan->toArray();

        foreach ($rules as $rule)
        {
            if (($rule[Entity::PAYMENT_MODE] === $input[Entity::PAYMENT_MODE]) and
                ($rule[Entity::PAYMENT_MODE_TYPE] === $input[Entity::PAYMENT_MODE_TYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $input[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $input[Entity::PAYMENT_ISSUER]))
            {
                throw new Exception\BadRequestException(
                    null,
                    BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestException($messages);
    }
}
