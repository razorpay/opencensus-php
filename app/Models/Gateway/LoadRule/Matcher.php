<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;
use RZP\Models\Terminal;

trait Matcher
{
    protected function getMatchingRuleForTerminal(Terminal\Entity $terminal, Base\PublicCollection $rules)
    {
        foreach ($rules as $rule)
        {
            // We compare terminal with rule for each comparison key. If all
            // comparisons are true we return the rule, else we return null
            foreach (Entity::COMPARISON_KEYS as $key)
            {
                $comparisonFunction = $this->getComparisonFunction($key);

                if ($this->$comparisonFunction($terminal, $rule) === false)
                {
                    return null;
                }
            }

            return $rule;
        }
    }

    protected function compareGateway(Terminal\Entity $terminal, Entity $rule)
    {
        return ($terminal->getGateway() === $rule->getGateway());
    }

    protected function compareGatewayAcquirer(Terminal\Entity $terminal, Entity $rule)
    {
        if ($terminal->getGatewayAcquirer() !== null)
        {
            return (($terminal->getGatewayAcquirer() === $rule->getGatewayAcquirer()) or
                    ($rule->getGatewayAcquirer() === Entity::ALL));
        }

        return true;
    }

    protected function compareInternational(Terminal\Entity $terminal, Entity $rule)
    {
        return ($terminal->isInternational() === $rule->isInternational());
    }

    protected function getComparisonFunction(string $key)
    {
        return 'compare' . studly_case($key);
    }
}
