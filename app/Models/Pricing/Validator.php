<?php

namespace RZP\Models\Pricing;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Card\Network;
use RZP\Models\Payment;
use RZP\Models\Payout;
use RZP\Models\Transfer;
use RZP\Models\Payment\Processor\Wallet;
use RZP\Models\Pricing;
use RZP\Models\Bank\IFSC;

class Validator extends Base\Validator
{
    protected static $addPlanRuleRules = [
        Entity::FEATURE             => 'sometimes|alpha',
        Entity::GATEWAY             => 'sometimes|',
        Entity::PLAN_NAME           => 'sometimes|',
        Entity::PAYMENT_METHOD      => 'required|string',
        Entity::PAYMENT_METHOD_TYPE => 'sometimes_if:payment_method,card|nullable|in:debit,credit',
        Entity::PAYMENT_NETWORK     => 'sometimes|nullable|alpha',
        Entity::PAYMENT_ISSUER      => 'sometimes_if:payment_method,card,emi|nullable|alpha|max:10',
        Entity::EMI_DURATION        => 'sometimes|nullable|integer|in:3,6,9,12,18,24',
        Entity::INTERNATIONAL       => 'sometimes|in:0,1',
        Entity::AMOUNT_RANGE_ACTIVE => 'sometimes|in:0,1',
        Entity::AMOUNT_RANGE_MIN    => 'required_only_if:amount_range_active,1|nullable|integer|min:0',
        Entity::AMOUNT_RANGE_MAX    => 'required_only_if:amount_range_active,1|nullable|integer|max:1000000000',
        Entity::PERCENT_RATE        => 'sometimes|integer|max:10000',
        Entity::FIXED_RATE          => 'sometimes|integer|max:100000',
        Entity::MIN_FEE             => 'sometimes|integer|max:100000',
        Entity::MAX_FEE             => 'sometimes|nullable|integer|max:100000',
    ];

    protected static $addPlanRuleValidators = [
        'addPlanRuleRate',
        'addPlanRuleNB',
        'addPlanRuleEmandate',
        'addPlanRulePaymentNetwork',
        'addPlanRuleInternational',
        'addPlanRuleAmountRange',
        'addPlanRuleFeature',
        'addPlanRulePricingMethod',
        'addPlanRuleMinAndMaxFee'
    ];

    protected static $createPlanRules = [
        Entity::PLAN_NAME   => 'required|alpha_num|max:20'
    ];

    protected static $createBulkPricingRules = [
        Entity::PLAN_NAME   => 'required|alpha_num|max:20',
        Entity::RULES       => 'required|array|min:1',
    ];

    protected function validateAddPlanRuleFeature($input)
    {
        if (empty($input[Pricing\Entity::FEATURE]))
        {
            return;
        }

        Pricing\Feature::validateFeature($input[Pricing\Entity::FEATURE]);
    }

    protected function validateAddPlanRulePricingMethod(array $input)
    {
        $feature = Pricing\Feature::PAYMENT;

        if (empty($input[Pricing\Entity::FEATURE]) === false)
        {
            $feature = $input[Pricing\Entity::FEATURE];
        }

        $method = $input[Pricing\Entity::PAYMENT_METHOD];

        switch ($feature)
        {
            case Pricing\Feature::PAYMENT:
                Payment\Method::validateMethod($method);

                break;

            case Pricing\Feature::PAYOUT:
                Payout\Method::validateMethod($method);

                break;

            case Pricing\Feature::TRANSFER:
                Transfer\ToType::validateDestination($method);

                break;
        }
    }

    protected function validateAddPlanRuleEmandate($input)
    {
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::EMANDATE)
        {
            $fields = array(
                Entity::PAYMENT_METHOD_TYPE,
                Entity::PAYMENT_ISSUER);

            foreach ($fields as $field)
            {
                if (isset($input[$field]) and
                    $input[$field] !== null)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PRICING_FIELD_NOT_REQUIRED_FOR_EMANDATE,
                        $field);
                }
            }
        }
    }

    protected function validateAddPlanRuleNB($input)
    {
        // Check that payment_method_type is not defined when mode is net-banking
        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING)
        {
            $fields = array(
                Entity::PAYMENT_METHOD_TYPE,
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

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::WALLET)
        {
            if (Wallet::exists($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for wallet should be a valid wallet name');
            }
        }

        if ($input[Entity::PAYMENT_METHOD] === Payment\Method::CARD)
        {
            $network = $input[Entity::PAYMENT_NETWORK];

            if (Network::isValidNetwork($network) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for card should be a valid card name');
            }

            if (Network::isUnsupportedNetwork($network) === true)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'This card payment network is not supported');
            }
        }

        if (($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING) or
            ($input[Entity::PAYMENT_METHOD] === Payment\Method::NETBANKING))
        {
            if (IFSC::exists($input[Entity::PAYMENT_NETWORK]) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Payment network for bank should be a valid bank name');
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
                'International pricing rule is only allowed for card method');
        }
    }

    protected function validateAddPlanRuleAmountRange($input)
    {
        if ((isset($input[Entity::AMOUNT_RANGE_ACTIVE]) === false) or
            ($input[Entity::AMOUNT_RANGE_ACTIVE] === '0'))
        {
            return;
        }

        if ((isset($input[Entity::AMOUNT_RANGE_MIN]) === false) or
            (isset($input[Entity::AMOUNT_RANGE_MAX]) === false))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require both min and max end of ranges');
        }

        if ($input[Entity::AMOUNT_RANGE_MIN] >= $input[Entity::AMOUNT_RANGE_MAX])
        {
            throw new Exception\BadRequestValidationFailureException(
                'Amount Range Rules require max end of ranges to be greater than'.
                'min end of range');
        }
    }

    protected function validateAddPlanRuleMinAndMaxFee($input)
    {
        if (isset($input[Entity::MAX_FEE]) and
            ($input[Entity::MIN_FEE] > $input[Entity::MAX_FEE]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Min fee chargeable for a rule needs to be greater than Max fee');
        }
    }

    public function createPlanValidate($input)
    {
        // If no plan name, then set it to null
        $planInput[Entity::PLAN_NAME] = $input[Entity::PLAN_NAME] ?? null;

        $this->validateInput('createPlan', $planInput);

        unset($input[Entity::PLAN_NAME]);

        $this->validateInput('addPlanRule', $input);
    }

    public function addPlanRuleValidate($input, Plan $plan)
    {
        $this->validateInput('addPlanRule', $input);

        // The plan should already have at least one rule
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
    public function validateRuleDoesNotMatch(Plan $plan)
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
                ($rule[Entity::AMOUNT_RANGE_MAX] === $newRule[Entity::AMOUNT_RANGE_MAX]) and
                ($rule[Entity::FEATURE] === $newRule[Entity::FEATURE]) and
                ($rule[Entity::EMI_DURATION] === $newRule[Entity::EMI_DURATION]))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PRICING_RULE_ALREADY_DEFINED);
            }

            if (($rule[Entity::PAYMENT_METHOD] === $newRule[Entity::PAYMENT_METHOD]) and
                ($rule[Entity::PAYMENT_METHOD_TYPE] === $newRule[Entity::PAYMENT_METHOD_TYPE]) and
                ($rule[Entity::PAYMENT_NETWORK] === $newRule[Entity::PAYMENT_NETWORK]) and
                ($rule[Entity::PAYMENT_ISSUER] === $newRule[Entity::PAYMENT_ISSUER]) and
                ($rule[Entity::INTERNATIONAL] === $newRule[Entity::INTERNATIONAL]) and
                ($rule[Entity::FEATURE] === $newRule[Entity::FEATURE]) and
                ($rule[Entity::EMI_DURATION] === $newRule[Entity::EMI_DURATION]) and
                (isset($newRule[Entity::AMOUNT_RANGE_ACTIVE]) === true) and
                (isset($rule[Entity::AMOUNT_RANGE_ACTIVE]) === true))
            {
                $this->checkPricingRuleForAmountRangeOverlap($rule, $newRule);
            }
        }
    }

    protected function checkPricingRuleForAmountRangeOverlap($rule, $newRule)
    {
        list($newRuleMin, $newRuleMax) = $newRule->getAmountRange();
        list($oldRuleMin, $oldRuleMax) = (new Entity($rule))->getAmountRange();

        //
        // We need to effectively check that the new pricing range does not overlap
        // with the existing pricing range.
        //
        // First we check that new range boundaries are not in between the old
        // range boundaries in any way.
        // This checks for all conditions except one.
        //
        // The old range should not be a subset of the new range and so we
        // also check for that.
        //
        // Max of one rule can be equal to min of another rule, and vice versa.
        // But min of one rule cannot be equal to min of another
        // and same for max. This needs to be ensure within the checks we have.
        //

        $flag = false;

        if (($this->between($newRuleMin, $oldRuleMin, $oldRuleMax)) or
            ($this->between($newRuleMax, $oldRuleMin, $oldRuleMax)) or
            ($newRuleMin === $oldRuleMin) or
            ($newRuleMax === $oldRuleMax))
        {
            $flag = true;
        }

        if (($this->between($oldRuleMin, $newRuleMin, $newRuleMax)) and
            ($this->between($oldRuleMax, $newRuleMin, $newRuleMax)))
        {
            $flag = true;
        }

        if ($flag)
        {
            throw new Exception\BadRequestValidationFailureException(
                PublicErrorDescription::BAD_REQUEST_PRICING_RULE_FOR_AMOUNT_RANGE_OVERLAP);
        }
    }

    public function validatePlanCountZero(Plan $plan)
    {
        if ($plan->count() > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_PLAN_WITH_SAME_NAME_EXISTS);
        }
    }

    protected function between($n, $min, $max)
    {
        return (($min < $n) and ($n < $max));
    }

}
