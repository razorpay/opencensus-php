<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Card;
use RZP\Models\Currency;
use RZP\Models\Pricing;
use RZP\Models\Pricing\Calculator\Tax\Base as TaxBase;
use RZP\Models\Transaction\FeeBreakup\Name as FeeBreakupName;
use RZP\Trace\TraceCode;

class Terminal extends Payment
{
    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        $entityName = $this->entity->getEntity();

        $this->getBasicPricingRule($pricing, $entityName);

        $this->traceAllRules($this->pricingRules);
    }

    protected function getBasicPricingRule(Pricing\Plan $pricing, $feature)
    {
        $method = $this->entity->getMethod();

        $filters = $this->getBasicPricingRuleFilters($this->product, $feature, $method);

        $rules = $this->applyFiltersOnRules($pricing, $filters);

        $rule = $this->getPricingRule($rules, $method);

        if ($rule === null)
        {
            throw new Exception\LogicException(
                'No appropriate pricing rule found for entity ' . $this->entity->getEntity(),
                ErrorCode::SERVER_ERROR_PRICING_RULE_ABSENT,
                ['entity' => $this->entity->toArray()]);
        }

        $this->pricingRules->push($rule);
    }

    protected function getPricingRule($rules, $method)
    {
        $rules = $this->getRelevantPricingRuleForGateway($rules);

        $rule = $this->getRelevantPricingRuleForMethod($rules, $method);

        return $rule;
    }

    protected function getRelevantPricingRuleForGateway($rules)
    {
        $issuer = $this->entity->getGateway();

        $filters = [
            [Pricing\Entity::GATEWAY, $issuer, true, null]
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $rules;
    }

    protected function redactSensitiveInfo(array $input)
    {
        unset($input[Pricing\Entity::PERCENT_RATE], $input[Pricing\Entity::FIXED_RATE]);
    }

    protected function setAmount()
    {
        $amount = $this->entity->getAmount();

        $currency = $this->entity->getCurrency();

        $merchantCurrency = Currency\Currency::INR;

        if(isset($this->entity) === true && isset($this->entity->merchant) === true)
        {
        $merchantCurrency = $this->entity->merchant->getCurrency();
        }

        $this->amount = (new Currency\Core)->getBaseAmount($amount, $currency, $merchantCurrency);
    }

    // Since we are not calculating tax for terminal entity and not using
    //fee split generated for the calculator/terminal entity, hence populating it as 0
    protected function calculateTax($fee)
    {
        $feeBreakup = $this->createFeeBreakup(FeeBreakupName::TAX, 0, 0);

        $this->feesSplit->push($feeBreakup);

        return 0;
    }

    public function validateFees($totalFees)
    {
        $amount = $this->amount;

        if ($totalFees > $amount)
        {
            $this->trace->info(
                TraceCode::BUY_PRICING_PAYMENT_FEES_GREATER_THAN_AMOUNT,
                [
                    'amount' => $amount,
                    'fees'   => $totalFees
                ]);
        }
    }

    protected function getRelevantPricingRuleForCardPayment($rules)
    {
        $payment = $this->entity;

        $cardType = $payment->card->getTypeElseDefault();

        $international = $payment->isInternational();

        $receiverType = $payment->getReceiverType();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      true,   null    ],
            [Pricing\Entity::RECEIVER_TYPE,         $receiverType,  true,   null    ],
            [Pricing\Entity::INTERNATIONAL,         $international, false,  false   ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForEmi($rules)
    {
        $payment = $this->entity;
        $emiPlan = $payment->emiPlan;
        $cardType = $payment->card->getTypeElseDefault();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $emiDuration = $emiPlan->getDuration();

        //Emi duration and issuer filter is for merchant subvented model
        //in normal emi it will be null where feature is payment
        $filters1 = array(
            [Pricing\Entity::PAYMENT_NETWORK,        $network,     true, null ],
            [Pricing\Entity::PAYMENT_METHOD_TYPE,    $cardType,    true, null ],
            [Pricing\Entity::EMI_DURATION,           $emiDuration, true, null ],
        );

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForEmandate($rules)
    {
        $payment = $this->entity;

        $bank = $payment->getBank();

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $bank, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForCardlessEmi($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForPayLater($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForNach($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }
}
