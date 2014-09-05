<?php

namespace Models\Pricing;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY             => 'sometimes|in:hdfc',
        Entity::PAYMENT_MODE        => 'required|alpha|in:card',
        Entity::PAYMENT_MODE_TYPE   => 'sometimes|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'sometimes|alpha|in:VISA,MC,DICL,RP,MAES',
        Entity::PAYMENT_ISSUER      => 'sometimes|alpha|max:10',
        Entity::PERCENT_RATE        => 'sometimes|numeric|max:10000',
        Entity::FIXED_RATE          => 'sometimes|numeric|max:100000');

    protected static $addPlanRuleValidators = array('addPlanRuleExtras');

    protected static $createPlanRules = array(
        Entity::PLAN_NAME => 'required|alpha_num|max:20');

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

    public function createPlanValidate($input)
    {
        $planInput[Entity::PLAN_NAME] =
            (isset($input[Entity::PLAN_NAME])) ? $input[Entity::PLAN_NAME] : null;

        $this->validateInput('createPlan', $planInput);

        unset($input[Entity::PLAN_NAME]);

        $this->validateInput('addPlanRule', $input);
    }

    public function addPlanRuleValidate(Plan $plan, $input)
    {
        $this->validateInput('addPlanRule', $input);

        $rule = $plan->first();

        $this->matchGateway($rule, $input);

        $this->matchPaymentRules($plan, $input);
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
                    null,
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
                    ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }
        }
    }

    protected function processValidationFailure($messages, $operation, $input)
    {
        throw new Exception\BadRequestException($messages);
    }

    public static function validatePlanCountZero($plan)
    {
        if ($plan->count() > 0)
        {
            throw new Exception\BadRequestException(
                null,
                ErrorCode::BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS);
        }
    }
}
