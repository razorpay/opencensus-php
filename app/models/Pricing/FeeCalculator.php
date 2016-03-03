<?php

namespace Models\Pricing;

use Constants\Mode;
use EE\Exception;
use Models\Card;
use Models\Payment;
use Models\Pricing;
use Services\SlackPoster;
use Trace\Trace;
use Trace\TraceCode;

class FeeCalculator
{
    const SERVICE_TAX_PERCENT = 14.5;

    /**
     * For which fees needs to be calculate.
     *
     * @var Models\Payment\Entity
     */
    protected $payment;

    /**
     * Pricing repository
     */
    protected $repo;

    protected $defaultPricingPlan = '1hDYlICobzOCYt';

    public function __construct($payment, $repo)
    {
        $this->payment = $payment;

        $this->repo = $repo;

        $this->trace = \Trace::getFacadeRoot();
    }

    public function calculate()
    {
        $payment = $this->payment;

        $pricingPlanId = $this->getPricingPlanId($payment->merchant);

        $rule = $this->getRelevantPricingRule($pricingPlanId, $payment);

        list($fee, $serviceTax) = $this->getFees($rule, $payment->getAmount());

        return array($fee, $serviceTax, $rule->getKey());
    }


    protected function getFees($rule, $amount)
    {
        $serviceTaxPercentage = self::SERVICE_TAX_PERCENT;

        list($percent, $fixed) = $rule->getRates();

        $fee = $this->getUnroundedFees($amount, $percent, $fixed);

        $fee = (int) ceil($fee);

        $serviceTax = (int) ceil(($fee * $serviceTaxPercentage) / 100);

        $fee += $serviceTax;

        assert ($fee < $amount);

        return  array($fee, $serviceTax);
    }

    protected function getRelevantPricingRule($pricingPlanId, $payment)
    {
        $method = $payment->getMethod();

        $pricing = $this->repo->getPricingRulesForMethod($pricingPlanId, $method);

        if ($method === Payment\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCard($pricing, $payment);
        }
        else
        {
            $rule = $this->getRelevantPricingRuleForMethod($pricing, $payment);
        }

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found', ['payment' => $payment->toArray()]);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForMethod($pricing, $payment)
    {
        return $this->validateAndGetOnePricingRule($pricing);
    }

    protected function getRelevantPricingRuleForCard($pricing, $payment)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.
        $rules = $pricing->all();

        // Fee based on the method type
        $cardType = $this->getCardType($payment);

        $international = $payment->isInternational();

        $network = Card\Network::getCode($payment->card->getNetwork());

        // Current Implementation
        // * Filter based on international
        // * Filter based on Network
        // * Filter based on Card Type
        // * Filter based on AmountRange
        // * Choose based on Amount

        // Structure is as follows:
        // Field name, Filed value, Choose default (true/false), default value
        $filters = array(
            [Pricing\Entity::INTERNATIONAL,         $international, false,  false   ],
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      true,   null    ],
            [Pricing\Entity::AMOUNT_RANGE_ACTIVE,   true,           true,   false   ],
        );

        foreach ($filters as $filter)
        {
            $rules = $this->filterRulesOnFieldByValue(
                $rules, $filter[0], $filter[1], $filter[2], $filter[3]);
        }

        $amount = $payment->getAmount();
        $rule = $this->chooseRuleWithAmount($rules, $amount);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'Failed to find a valid pricing rule for the payment',
                [
                    'rules' => $rules->toArray(),
                    'amount' => $amount,
                    'network' => $network,
                    'type', $cardType
                ]);
        }

        return $rule;
    }

    /**
     * Filter pricing rules based on fieldName and fieldValue
     * If the value is not found, and a default value is allowed,
     * matches based on default value will be returned.
     *
     * @param  $rules       List of rules
     * @param  $filedName   Field name to be filtered on
     * @param  $filedValue  Filed value to be filtered on
     * @param  $chooseDefault Default value to be considered if field value not found
     * @param  $defaultValue  Default value to be filtered on if $chooseDefualt is true
     */
    protected function filterRulesOnFieldByValue(
        $rules,
        $fieldName,
        $fieldValue,
        $chooseDefault = true,
        $defaultValue = null)
    {
        $matchRules         = [];
        $defaultMatchRules  = [];

        foreach ($rules as $rule)
        {
            if ($rule->getAttribute($fieldName) === $fieldValue)
            {
                $matchRules[] = $rule;
            }
            else if (($chooseDefault === true) and
                     ($rule->getAttribute($fieldName) === $defaultValue))
            {
                $defaultMatchRules[] = $rule;
            }
        }

        if (empty($matchRules))
        {
            return $defaultMatchRules;
        }

        return $matchRules;
    }

    /**
     * If the rules are amount range active rules,
     * choose rule based on amount
     * else return first available rule.
     */
    protected function chooseRuleWithAmount($rules, $amount)
    {
        $relevantRule = null;

        // Either all the rules will be amount range active,
        // Else none will be, so test against only one.
        if ($rules[0]->isAmountRangeActive())
        {
            foreach ($rules as $rule)
            {
                if (($rule->getAmountRangeMin() < $amount) and
                    ($rule->getAmountRangeMax() >= $amount))
                {
                    $relevantRule = $rule;
                    break;
                }
            }

        }
        // If only one other possible rule, return it.
        else if (count($rules) === 1)
        {
            return $rules[0];
        }
        else
        {
            // Ideally should not reach this case, ever.
        }

        return $relevantRule;
    }

    protected function getCardType($payment)
    {
        // Fee based on the method type
        $cardType = $payment->card->getType();

        if ($cardType === Card\Type::UNKNOWN)
        {

            // Disabling until IIN
            // import is complete
            /*
            $slackArray = ['id' => $payment->card->getDashboardEntityLinkForSlack() ];

            $this->slackPost(
                'Unknown card type found',
                $slackArray,
                ['channel' => '#tech_logs']);

            */
            $cardType = Card\Type::DEBIT;
        }

        return $cardType;
    }

    protected function validateAndGetOnePricingRule($pricing)
    {
        if (count($pricing) > 1)
        {
            throw new Exception\LogicException(
                'Only 1 pricing rule should have been present here. Found: ' . count($pricing),
                [$pricing->toArray()]);
        }

        $rule = $pricing[0];

        return $rule;
    }


    protected function getPricingPlanId($merchant)
    {
        $pricingPlanId = $merchant->getPricingPlanId();

        if ($pricingPlanId !== null)
        {
            return $pricingPlanId;
        }

        return $this->getDefaultPricingPlan();
    }

    protected function getDefaultPricingPlan()
    {
        $mode = \BasicAuth::getMode();

        // In live, pricing plan for merchant cannot be null.
        if ($mode === Mode::LIVE)
        {
            throw new Exception\LogicException(
                'No pricing plan assigned for merchant id: ' . $merchant->getKey());
        }

        // In test, we can return a default pricing plan if it's not set for merchant.
        return $this->defaultPricingPlan;
    }

    protected function getUnroundedFees($amount, $percent, $fixed)
    {
        return (($amount * $percent) / 10000) + $fixed;
    }
}