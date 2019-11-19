<?php

namespace RZP\Models\Pricing\Calculator;

use RZP\Exception;
use RZP\Models\Org;
use RZP\Models\Card;
use RZP\Models\Pricing;
use RZP\Error\ErrorCode;
use RZP\Models\Pricing\Fee;
use RZP\Models\Base as BaseModel;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Payment as PaymentModel;

class Payment extends Base
{
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
        $rules = $this->getRelevantPricingRulesForFeeBearer($rules);

        $rules = $this->getRelevantPricingRuleForProcurer($rules);

        $rule = $this->getRelevantPricingRuleForMethod($rules, $method);

        return $rule;
    }

    protected function getRelevantPricingRulesForFeeBearer($rules)
    {
        $merchant = $this->entity->merchant;

        if ($merchant === null)
        {
            return $rules;
        }

        $feeBearer = $merchant->getFeeBearer();

        if ($feeBearer === FeeBearer::DYNAMIC)
        {
            return $rules;
        }

        $filters = [
            [Pricing\Entity::FEE_BEARER, $feeBearer, true, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }


    protected function getRelevantPricingRuleForMethod($rules, $method)
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

    protected function getRelevantPricingRuleForProcurer($rules)
    {
        $payment = $this->entity;

        if ($payment->merchant->isFeeBearerCustomerOrDynamic() === true)
        {
            return $rules;
        }

        //
        // Transfer method doesn't have terminal associated
        //
        if ($payment->getMethod() === PaymentModel\Method::TRANSFER)
        {
            return $rules;
        }

        $procurer = $payment->terminal->getProcurer();

        $filters = [
            [Pricing\Entity::PROCURER, $procurer, true, null]
        ];

        return $this->applyFiltersOnRules($rules, $filters);
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

        $subtype = $payment->card->getSubtype();

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
            [Pricing\Entity::PAYMENT_METHOD_TYPE,       $cardType,      true,   null    ],
            [Pricing\Entity::PAYMENT_METHOD_SUBTYPE,    $subtype,       true,   null    ],
            [Pricing\Entity::AUTH_TYPE,                 $authType,      true,   null    ],
            [Pricing\Entity::PAYMENT_ISSUER,            $issuer,        true,   null    ],
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

    /**
     *
     * Ensure that all the pricing rules have the same fee_bearer value.
     * @param  $pricingRules
     * @return return the common fee_bearer value
     * @throws Exception\LogicException when pricingRules has more than 1 type of fee_bearer value
     */
    public function validateAndGetFeeBearer() : string
    {
        $pricingRules = $this->pricingRules;

        if (count($pricingRules) < 1)
        {
            throw new Exception\LogicException(
                'No pricing rule found. Expected atleast 1');
        }

        $feeBearers = [];

        foreach ($pricingRules as $rule)
        {
            array_push($feeBearers, $rule->getFeeBearer());
        }

        $feeBearersUnique = array_unique($feeBearers);

        if (count($feeBearersUnique) !== 1)
        {
            throw new Exception\LogicException(
                'Expected only one type of feebearer for all rules. Found: ' . $feeBearers);
        }

        return $pricingRules[0]->getFeeBearer();
    }

    /*
     * Even though function says "get", no rule is getting returned here.
     * This is because even the parent class function has the same behavior.
     */
    public function getRelevantPricingRule(Pricing\Plan $pricing)
    {
        parent::getRelevantPricingRule($pricing);

        $feeBearer = $this->validateAndGetFeeBearer($this->pricingRules);

        $payment = $this->entity;
        // this is an side effect that is unavoidable.
        $payment->setFeeBearer($feeBearer);

    }

    protected function isFeeBearerCustomer()
    {
        $payment = $this->entity;

        return ($payment->isFeeBearerCustomer() === true);
    }

    protected function setAmount()
    {
        $amount = $this->entity->getBaseAmount();

        if ($this->isFeeBearerCustomerOrDynamic() === true)
        {
            // 1. The first call will have the fee = 0,
            //    hence fees will be calculated on the original amount
            // 2. On validation/capture call, the fee will be set
            $amount = $amount - $this->entity->getFee();
        }

        $this->amount = $amount;
    }
}
