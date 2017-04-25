<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;

class Filter extends Core
{
    protected $merchant;

    protected $payment;

    protected $card;

    protected $rules;

    const PROPERTIES = [
        Entity::CARD_TYPE,
        Entity::INTERNATIONAL,
        Entity::NETWORK,
        Entity::ISSUER,
    ];

    public function __construct(Base\PublicCollection $rules, array $input)
    {
        $this->rules = $rules;

        $this->merchant = $input['merchant'];

        $this->payment = $input['payment'];

        if ($this->payment->hasCard() === true)
        {
            $this->card = $this->payment->card;
        }
    }

    /**
     * Filters rules by matching on the attributes defined in the PROPERTIES
     * array. Used to get the rules best matching the payment criteria
     *
     * @return Base\PublicCollection Collection of filtered rules
     */
    public function filter()
    {
        // We check if any merchant specific rules are present. If present we only deal with
        // those rules as our rule set and discard any other rules
        $merchantSpecificRules = $this->getMerchantSpecificRules();

        if ($merchantSpecificRules->isEmpty() === false)
        {
            $this->rules = $merchantSpecificRules;
        }

        foreach (self::PROPERTIES as $filterProperty)
        {
            $filterFunction = $this->getFilterFunctionForProperty($filterProperty);

            foreach ($this->rules as $rule)
            {
                if ($this->$filterFunction($rule) === true)
                {
                    $filteredRules->push($rule);
                }
            }
        }

        // In some cases, it can happen that a particular rule satisfies multiple filters
        // For e.g a rule for card_type = credit and network = VISA satisfies both card_type
        // and network filters for a VISA credit card payment and hence gets puhed to
        // filtered rules. We run a unique check to remove such duplicates
        $filteredRules = $filteredRules->unique(function ($rule)
        {
            return $rule->getId();
        });

        if ($filteredRules->isEmpty() === false)
        {
            return $filteredRules;
        }

        // In case no rules satisfy the filter rule criteria, we retuen the set of
        // all rules as it may contain rules with the filter attribute value as null.
        // E.g a rule for card payments across all networks and issuers will be rejected
        // by above filters but is still eligible for a payment
        return $this->rules;
    }

    protected function cardTypeFilter(Entity $rule)
    {
        if ($this->card === null)
        {
            return true;
        }

        return ($this->card->getType() === $rule->getCardType());
    }

    protected function networkFilter(Entity $rule)
    {
        if ($this->card === null)
        {
            return true;
        }

        return ($this->card->getNetworkCode() === $rule->getNetwork());
    }

    protected function issuerFilter(Entity $rule)
    {
        $method = $this->payment->getMethod();

        switch ($method)
        {
            case Payment\Method::CARD:
            case Payment\Method::EMI:
                return ($this->card->getIssuer() === $rule->getIssuer());

            case Payment\Method::NETBANKING:
                return ($this->payment->getBank() === $rule->getIssuer());

            case Payment\Method::WALLET:
                return ($this->payment->getWallet() === $rule->getIssuer());

            default:
                return true;
        }
    }

    protected function internationalFilter(Entity $rule)
    {
        // Don't filter if method is not card or emi
       if ($this->payment->isMethodCardOrEmi() === false)
       {
            return true;
       }

       if ($this->payment->isInternational() === true)
       {
            return $rule->isInternational();
       }

       return false;
    }

    protected function getMerchantSpecificRules()
    {
        $merchantId = $this->merchant->getId();

        $merchantSpecificRules = $this->rules->filter(function ($rule) use ($merchantId)
        {
            return ($rule->getMerchantId() === $merchantId);
        });

        return $merchantSpecificRules;
    }

    protected function getFilterFunctionForProperty(string $property)
    {
        return camel_case($property) . 'Filter';
    }
}
