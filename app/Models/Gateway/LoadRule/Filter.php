<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;
use RZP\Models\Payment;

class Filter extends Core
{
    protected $merchant;

    protected $payment;

    protected $card;

    public function __construct(array $input)
    {
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
     * @param Base\PublicCollection Rules to apply filters on
     * @return Base\PublicCollection Collection of filtered rules
     */
    public function filter(Base\PublicCollection $rules)
    {
        // We check if any merchant specific rules are present. If present we only deal with
        // those rules as our rule set and discard any other rules
        $merchantSpecificRules = $this->getMerchantSpecificRules($rules);

        if ($merchantSpecificRules->isEmpty() === false)
        {
            $rules = $merchantSpecificRules;
        }

        foreach (Entity::FILTER_ATTRIBUTES as $filterProperty)
        {
            $rules = $rules->filter(function ($rule) use ($filterProperty)
            {
                $filterFunction = $this->getFilterFunctionForProperty($filterProperty);

                $result = $this->$filterFunction($rule);

                // For certain attributes, the value can be null, meaning that any/all values are
                // acceptable. In those cases the filter passes if the attribute value is null
                if ($result === false)
                {
                    if (in_array($filterProperty, Entity::NULLABLE_ATTRIBUTES, true) === true)
                    {
                        $result = ($rule->getAttribute($filterProperty) === null);
                    }
                }

                return $result;
            });
        }

        return $rules;
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

       return true;
    }

    protected function getMerchantSpecificRules(Base\PublicCollection $rules)
    {
        $merchantId = $this->merchant->getId();

        $merchantSpecificRules = $rules->filter(function ($rule) use ($merchantId)
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
