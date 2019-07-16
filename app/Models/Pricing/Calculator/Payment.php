<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Models\Card;
use RZP\Models\Pricing;
use RZP\Models\Base as BaseModel;
use RZP\Models\Payment as PaymentModel;

class Payment extends Base
{
    public function __construct(BaseModel\PublicEntity $entity, string $product)
    {
        parent::__construct($entity, $product);
    }

    protected function getAddOnPricingRule(Pricing\Plan $pricing, array $features, $entityName)
    {
        $method  = $this->entity->getMethod();
        $product = $this->product;

        foreach ($features as $feature)
        {
            $filters = [
                [Pricing\Entity::PRODUCT,        $product, false, null],
                [Pricing\Entity::FEATURE,        $feature, false, null],
                [Pricing\Entity::PAYMENT_METHOD, $method,  false, null],
            ];

            $rules = $this->applyFiltersOnRules($pricing, $filters);

            if (count($rules) > 0)
            {
                $rule = $this->getPricingRule($rules, $method);

                $this->pricingRules->push($rule);
            }
        }
    }

    protected function getPricingRule($rules, $method)
    {
        $rule = null;

        if ($method === PaymentModel\Method::CARD)
        {
            $rule = $this->getRelevantPricingRuleForCardPayment($rules);
        }
        else if ($method === PaymentModel\Method::WALLET)
        {
            $rule = $this->getRelevantPricingRuleForWalletPayment($rules);
        }
        else if ($method === PaymentModel\Method::NETBANKING)
        {
            $rule = $this->getRelevantPricingRuleForNBPayment($rules);
        }
        else if ($method === PaymentModel\Method::UPI)
        {
            $rule = $this->getRelevantPricingRuleForUPI($rules);
        }
        else if ($method === PaymentModel\Method::AEPS)
        {
            $rule = $this->getRelevantPricingRuleForAeps($rules);
        }
        else if ($method === PaymentModel\Method::EMANDATE)
        {
            $rule = $this->getRelevantPricingRuleForEmandate($rules);
        }
        else if ($method === PaymentModel\Method::EMI)
        {
            $rule = $this->getRelevantPricingRuleForEmi($rules);
        }
        else if ($method === PaymentModel\Method::BANK_TRANSFER)
        {
            $rule = $this->getRelevantPricingRuleForBankTransfer($rules);
        }
        else if ($method === PaymentModel\Method::CARDLESS_EMI)
        {
            $rule = $this->getRelevantPricingRuleForCardlessEmi($rules);
        }
        else if ($method === PaymentModel\Method::PAYLATER)
        {
            $rule = $this->getRelevantPricingRuleForPayLater($rules);
        }
        // else if ($method === PaymentModel\Method::TRANSFER)
        // {
        //     $rule = $this->getRelevantPricingRuleForTransfer($rules);
        // }
        else
        {
            $rule = $this->validateAndGetOnePricingRule($rules);
        }

        return $rule;
    }

    protected function getRelevantPricingRuleForCardPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        // Fee based on the method type
        $payment = $this->entity;

        $cardType = $payment->card->getTypeElseDefault();

        $international = $payment->isInternational();

        $receiverType = $payment->getReceiverType();

        $authType = $payment->getAuthType();

        $network = Card\Network::getCode($payment->card->getNetwork());

        $issuer = $payment->card->getIssuer();

        // Current Implementation
        // * Filter based on receiver type
        // * Filter based on international
        // * Filter based on Network
        // * Filter based on Auth Type
        // * If its amex, then stop
        // * Filter based on Card Type
        // * Filter based on AmountRange
        // * Choose based on Amount

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value

        // The sequence should not be changed as it changes the behaviour.
        // Right now if the receiver_type is present it needs to be selected no
        // matter what otherwise default type is used
        $filters1 = [
            [Pricing\Entity::RECEIVER_TYPE,         $receiverType,  true,   null    ],
            [Pricing\Entity::INTERNATIONAL,         $international, false,  false   ],
            [Pricing\Entity::PAYMENT_NETWORK,       $network,       true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        if ($network === Card\Network::AMEX)
        {
            return $this->validateAndGetOnePricingRule($rules);
        }

        if ($cardType === Card\Type::PREPAID)
        {
            $filterPrepaid = [
                [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      false,   null    ],
            ];

            $prepaidRules = $this->applyFiltersOnRules($rules, $filterPrepaid);

            if (empty($prepaidRules) === true)
            {
                $cardType = Card\Type::CREDIT;
            }
        }

        // If network is not amex, we can check for AMOUNT RANGE FILTERS

        $filters2 = [
            [Pricing\Entity::PAYMENT_METHOD_TYPE,   $cardType,      true,   null    ],
            [Pricing\Entity::AUTH_TYPE,             $authType,      true,   null    ],
            [Pricing\Entity::PAYMENT_ISSUER,        $issuer,        true,   null    ],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters2);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForWalletPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $wallet = $payment->getWallet();

        // Current Implementation
        // * Filter based on wallet

        // Structure is as follows:
        // Field name, Field value, Choose default (true/false), default value
        $filter = array(
            [Pricing\Entity::PAYMENT_NETWORK, $wallet, true, null]
        );

        $rules = $this->applyFiltersOnRules($rules, $filter);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForNBPayment($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $bank = $payment->getBank();

        // Current Implementation
        // * Filter based on AmountRange
        // * Choose based on Amount

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK, $bank, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForUPI($rules)
    {
        $payment = $this->entity;

        $receiverType = $payment->getReceiverType();

        $filters1 = [
            [Pricing\Entity::RECEIVER_TYPE, $receiverType, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForAeps($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForEmandate($rules)
    {
        // All the rules for the current pricing plan will be put
        // through various filters till the right pricing rule
        // for the current case remains.

        $payment = $this->entity;

        $bank = $payment->getBank();

        $authType = $payment->getGlobalOrLocalTokenEntity()->getAuthType();

        $recurringType = $payment->getRecurringType();

        // Current Implementation
        // * Filter based on AmountRange
        // * Choose based on Amount
        // * Choose based on Authentication type
        // * Choose based on Recurring type

        $filters = [
            [Pricing\Entity::PAYMENT_NETWORK,     $bank,          true, null],
            [Pricing\Entity::PAYMENT_METHOD_TYPE, $authType,      true, null],
            [Pricing\Entity::PAYMENT_ISSUER,      $recurringType, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForEmi($rules)
    {
        $payment = $this->entity;
        $emiPlan = $payment->emiPlan;

        $network = Card\Network::getCode($payment->card->getNetwork());

        $emiDuration = $emiPlan->getDuration();

        $issuer = $emiPlan->getIssuer();

        //Emi duration and issuer filter is for merchant subvented model
        //in normal emi it will be null where feature is payment
        $filters1 = array(
            [Pricing\Entity::PAYMENT_NETWORK, $network,     true, null ],
            [Pricing\Entity::PAYMENT_ISSUER,  $issuer,      true, null ],
            [Pricing\Entity::EMI_DURATION,    $emiDuration, true, null ]
        );

        $rules = $this->applyFiltersOnRules($rules, $filters1);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForBankTransfer($rules)
    {
        return $this->applyAmountRangeFilterAndReturnOneRule($rules);
    }

    protected function getRelevantPricingRuleForCardlessEmi($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        // @todo: Pricing structure to do discussed with product
        $filters = [
            [Pricing\Entity::PAYMENT_ISSUER, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }

    protected function getRelevantPricingRuleForPayLater($rules)
    {
        $payment = $this->entity;

        $provider = $payment->getWallet();

        $filters = [
            [Pricing\Entity::PAYMENT_ISSUER, $provider, true, null],
        ];

        $rules = $this->applyFiltersOnRules($rules, $filters);

        return $this->validateAndGetOnePricingRule($rules);
    }
}
