<?php

namespace Models\Pricing;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = array(
        Entity::GATEWAY             => 'sometimes|in:hdfc',
        Entity::PAYMENT_MODE        => 'required|alpha|in:card',
        Entity::PAYMENT_MODE_TYPE   => 'required_if:payment_mode,card|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'required_if:payment_mode,card|alpha|in:VISA,MC,DICL,RP,MAES',
        Entity::PAYMENT_ISSUER      => 'sometimes|alpha|max:10',
        Entity::PERCENT_RATE        => 'sometimes|numeric',
        Entity::FIXED_RATE          => 'sometimes|numeric');

    protected static $addPlanRuleValidators = array('addPlanRuleExtras');

    protected static $addPlanRules = array(
        Entity::Plan => 'required|alpha|max:20');

    protected function validateAddPlanRuleExtras($input)
    {
        if ((isset($input[Entity::PERCENT_RATE]) === false) and
            (isset($input[Entity::FIXED_RATE]) === false))
        {
            throw new Exception\BadRequestException(
                'One of percent_rate and fixed_rate must be present');
        }
    }

    public static function createPlanValidate($input)
    {
        try
        {
            $instance = new static;

            $instance->validateInput($input, 'addPlan');

            unset($input[Entity::PLAN]);

            $instance->validateInput($input, 'addPlan');
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new Exception\BadRequestException($e->getMessageBag(), 0, $e);
        }
    }

    public static function addPlanRuleValidate(Plan $plan, $input)
    {
        $instance = new static;

        try
        {
            $instance->validateInput($input, 'addPlanRule');
        }
        catch (Exception\ValidationFailureException $e)
        {
            throw new Exception\BadRequestException($e->getMessageBag(), 0, $e);
        }

        $items = $plan->getItems();
        $rule = $item[0];

        $instance->matchGateway($rule, $input);

        $plan->matchPaymentRules($input);
    }

    protected function matchGateway($plan, $input)
    {
        $gateway = $rule->getGateway();

        if ($gateway !== null)
        {
            if ((isset($input[Entity::GATEWAY]) === false) or
                ($input[Entity::GATEWAY] !== $gateway))
            {
                throw new Exception\BadRequestException(
                    'gateway must be set for this plan with value: ' . $gateway);
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
                    'The new rule matches with an active existing rule and ' .
                    'hence is not being set');
            }
        }
    }
}
