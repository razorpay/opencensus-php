<?php

namespace Models\Pricing;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Payment;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY             => 'sometimes|',
        Entity::PAYMENT_METHOD      => 'required|alpha|in:card,netbanking,wallet,emi',
        Entity::PAYMENT_METHOD_TYPE => 'sometimes_if:payment_method,card|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'sometimes_if:payment_method,card|alpha|in:VISA,MC,DICL,RP,MAES,RUPAY,AMEX',
        Entity::PAYMENT_ISSUER      => 'sometimes_if:payment_method,card|alpha|max:10',
        Entity::INTERNATIONAL       => 'sometimes|in:0,1',
        Entity::AMOUNT_RANGE_ACTIVE => 'sometimes|in:0,1',
        Entity::AMOUNT_RANGE_MIN    => 'required_only_if:amount_range_active,1|integer',
        Entity::AMOUNT_RANGE_MAX    => 'required_only_if:amount_range_active,1|integer',
        Entity::PERCENT_RATE        => 'sometimes|integer|max:10000',
        Entity::FIXED_RATE          => 'sometimes|integer|max:100000');

    protected static $addPlanRuleValidators = array(
        'addPlanRuleRate',
        'addPlanRuleNB',
        'addPlanRulePaymentNetwork',
        'addPlanRuleInternational',
        'addPlanRuleAmountRange');

    protected static $createPlanRules = array(
        Entity::PLAN_NAME => 'required|alpha_num|max:20');

    protected function validateAddPlanRuleNB($input)
    {
        // Check that payment_method_type is not defined when mode is net-banking
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING)
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

    protected function validateAddPlanRulePaymentNetwork($input)
    {
        if ((isset($input[Entity::PAYMENT_NETWORK]) === false) or
            ($input[Entity::PAYMENT_NETWORK] === null))
        {
            return;
        }

        if (($input[Entity::PAYMENT_METHOD] !== Payment\Method::CARD ) and
            ($input[Entity::PAYMENT_METHOD] !== Payment\Method::EMI))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment network only needs to be passed when payment method is card or emi');
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

    protected function validateAddPlanRuleInternational($input)
    {
        if ((isset($input[Entity::INTERNATIONAL]) === false) or
            ($input[Entity::INTERNATIONAL] === '0'))
        {
            return;
        }

        $attrs = array(
            Entity::PAYMENT_NETWORK,
            Entity::PAYMENT_ISSUER,
            Entity::PAYMENT_METHOD_TYPE,
        );

        foreach ($attrs as $attr)
        {
            if ((isset($input[$attr])) and
                ($input[$attr] !== null))
            {
                throw new Exception\BadRequestValidationFailureException(
                    "For international pricing rule, attribute $attr should not be set");
            }
        }

        if ($input[Entity::PAYMENT_METHOD] !== Payment\Method::CARD)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Internatioanl pricing rule is only allowed for card method');
        }
    }

    protected function validateAddPlanRuleAmountRange($input)
    {
        if ((isset($input[Entity::AMOUNT_RANGE_ACTIVE]) === false) or
            ($input[Entity::AMOUNT_RANGE_ACTIVE] === '0'))
        {
            return;
        }

        if ((!isset($input[Entity::AMOUNT_RANGE_MIN])) or
            (!isset($input[Entity::AMOUNT_RANGE_MAX])))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require both min and max end of ranges');
        }

        if ($input[Entity::AMOUNT_RANGE_MIN] < Payment\Entity::MIN_PAYMENT_AMOUNT)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules min end of range has to be atleast '.
                Payment\Entity::MIN_PAYMENT_AMOUNT);
        }

        if ($input[Entity::AMOUNT_RANGE_MIN] > $input[Entity::AMOUNT_RANGE_MAX])
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require max end of ranges to be greater than'.
                'min end of range');
        }

        if ($input[Entity::PAYMENT_METHOD] !== Payment\Method::CARD)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules are only allowed for card method');
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
    public function matchPaymentRules($plan)
    {
        $rules = $plan->toArray();

        $newRule = $this->entity;

        foreach ($rules as $rule)
        {
            if (($rule[Entity::PAYMENT_METHOD] === $newRule[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $newRule[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $newRule[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $newRule[Entity::PAYMENT_ISSUER]) and
                ($rule[Entity::INTERNATIONAL] === $newRule[Entity::INTERNATIONAL]) and
                ($rule[Entity::AMOUNT_RANGE_ACTIVE] === $newRule[Entity::AMOUNT_RANGE_ACTIVE]) and
                ($rule[Entity::AMOUNT_RANGE_MIN] === $newRule[Entity::AMOUNT_RANGE_MIN]) and
                ($rule[Entity::AMOUNT_RANGE_MAX] === $newRule[Entity::AMOUNT_RANGE_MAX]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }

            $this->checkPricingRuleForOverlap($rule, $newRule);
        }
    }

    protected function checkPricingRuleForOverlap($rule, $newRule)
    {
        if (($newRule[Entity::PAYMENT_METHOD] == Payment\Method::CARD) and
                $rule[Entity::AMOUNT_RANGE_ACTIVE] and
                 $newRule[Entity::AMOUNT_RANGE_ACTIVE])
        {
            if(
                (($newRule[Entity::AMOUNT_RANGE_MAX] > $rule[Entity::AMOUNT_RANGE_MIN]) and
                    ($newRule[Entity::AMOUNT_RANGE_MIN] < $rule[Entity::AMOUNT_RANGE_MIN])) or
                (($rule[Entity::AMOUNT_RANGE_MAX] > $newRule[Entity::AMOUNT_RANGE_MIN]) and
                    ($rule[Entity::AMOUNT_RANGE_MIN] < $newRule[Entity::AMOUNT_RANGE_MIN])) or
                (($rule[Entity::AMOUNT_RANGE_MAX] > $newRule[Entity::AMOUNT_RANGE_MAX]) and
                    ($rule[Entity::AMOUNT_RANGE_MIN] < $newRule[Entity::AMOUNT_RANGE_MIN]))
            )
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
