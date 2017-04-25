<?php

namespace RZP\Models\Gateway\LoadRule;

use RZP\Models\Base;
use RZP\Models\Terminal;

trait Matcher
{
    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @return bool whether rule matches terminal
     */
    public function matches(Terminal\Entity $terminal): bool
    {
        foreach (self::COMARISON_KEYS as $key)
        {
            $comparisonFunction = $this->getComparisonFunction($key);

            if ($this->$comparisonFunction($terminal) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function compareGateway(Terminal\Entity $terminal)
    {
        return ($terminal->getGateway() === $this->getGateway());
    }

    protected function compareGatewayAcquirer(Terminal\Entity $terminal)
    {
        if ($terminal->getGatewayAcquirer() !== null)
        {
            return (($terminal->getGatewayAcquirer() === $this->getGatewayAcquirer()) or
                    ($this->getGatewayAcquirer() === null));
        }

        return true;
    }

    protected function compareInternational(Terminal\Entity $terminal)
    {
        return ($terminal->isInternational() === $rule->isInternational());
    }

    protected function getComparisonFunction(string $key)
    {
        return 'compare' . studly_case($key);
    }
}
