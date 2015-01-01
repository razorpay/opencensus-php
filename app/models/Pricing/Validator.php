<?php

namespace Models\Pricing;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY             => 'sometimes|in:hdfc',
        Entity::PAYMENT_METHOD        => 'required|alpha_space|in:card,netbanking',
        Entity::PAYMENT_METHOD_TYPE   => 'sometimes|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'sometimes|alpha|in:VISA,MC,DICL,RP,MAES',
        Entity::PAYMENT_ISSUER      => 'sometimes|alpha|max:10',
        Entity::PERCENT_RATE        => 'sometimes|numeric|max:10000',
        Entity::FIXED_RATE          => 'sometimes|numeric|max:100000');

    protected static $addPlanRuleValidators = array(
        'addPlanRuleRate',
        'addPlanRuleNB');

    protected static $createPlanRules = array(
        Entity::PLAN_NAME => 'required|alpha_num|max:20');

    protected function validateAddPlanRuleNB($input)
    {
        // Check that payment_method_type is not defined when mode is net-banking
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::NET_BANKING)
        {
            $fields = array(
                Entity::PAYMENT_METHOD_TYPE,
                Entity::PAYMENT_NETWORK,
                Entity::PAYMENT_ISSUER);

            foreach ($fields as $field)
            {
                if (isset($input[$field]) and
                    $input[$field] !== null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PRICING_FIELD_NOT_REQUIRED_FOR_NB,
                        $field);
                }
            }
        }
    }

    protected function validateAddPlanRuleRate($input)
    {
        //
        // At least one of percent_rate and fixed_rate has to be specified
        //
        if ((isset($input[Entity::PERCENT_RATE]) === false) and
            (isset($input[Entity::FIXED_RATE]) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_RATE_NOT_DEFINED);
        }
    }

    public function createPlanValidate($input)
    {
        // If no plan name, then set it to null
        $planInput[Entity::PLAN_NAME] =
            (isset($input[Entity::PLAN_NAME])) ? $input[Entity::PLAN_NAME] : null;

        $this->validateInput('createPlan', $planInput);

        unset($input[Entity::PLAN_NAME]);

        $this->validateInput('addPlanRule', $input);
    }

    public function addPlanRuleValidate($input, Plan $plan)
    {
        $this->validateInput('addPlanRule', $input);

        // The plan should already have at leats one rule
        if ($plan->count() === 0)
        {
            throw new Exception\LogicException(
                'No plan rule exists for the defined plan. Blasphemy!');
        }

        $rule = $plan->first();

        // The input and pricing rule (any) gateway should match
        $this->matchGateway($rule, $input);

        // The input should not match any existing rule
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
                    ErrorCode::BAD_REQUEST_PRICING_GATEWAY_REQUIRED);
            }
        }
    }

    /**
     * Check whether this new rule already exists
     */
    public function matchPaymentRules($plan, $input)
    {
        $rules = $plan->toArray();

        foreach ($rules as $rule)
        {
            if (($rule[Entity::PAYMENT_METHOD] === $input[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $input[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $input[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $input[Entity::PAYMENT_ISSUER]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }
        }
    }

    public static function validatePlanCountZero($plan)
    {
        if ($plan->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS);
        }
    }
}
