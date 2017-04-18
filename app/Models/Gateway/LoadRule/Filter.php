<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;

class Filter extends Core
{
    protected $merchant;

    protected $payment;

    protected $card;

    protected $rules;

    // TODO: Skipping category for now in merchant filter
    const PROPERTIES = [
        Entity::CARD_TYPE,
        Entity::NETWORK,
        Entity::ISSUER,
        Entity::INTERNATIONAL,
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

    public function filter()
    {
        $filteredRules = $this->rules;

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

        // It may happen that a single rule gets pushed multiple times when we
        // pass the rules through above filters. Running a unique check to remove
        // duplicate rules
        $filteredRules = $filteredRules->unique(function ($rule)
        {
            return $rule->getId();
        });

        if ($filteredRules->isEmpty() === false)
        {
            return $filteredRules;
        }

        // If there are no rules satisfying the filter criteria, we return the set
        // of all rules, as they can contain attributes with value ALL
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

            default:
                return true;
        }
    }

    protected function internationalFilter(Entity $rule)
    {
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

    protected function getFilterFunctionForProperty(string $property)
    {
        return camel_case($property) . 'Filter';
    }
}
