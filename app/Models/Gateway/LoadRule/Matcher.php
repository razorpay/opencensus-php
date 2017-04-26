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
        foreach (self::COMPARISON_KEYS as $key)
        {
            $comparisonFunction = $this->getComparisonFunction($key);

            if ($this->$comparisonFunction($terminal) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function compareGateway(Terminal\Entity $terminal): bool
    {
        return ($terminal->getGateway() === $this->getGateway());
    }

    /**
     * Compares terminal gateway acquirer to rule's gateway acquirer, also returns
     * true if the rule's gateway acquirer is null, meaning all acquirers
     * @param  Terminal\Entity $terminal [description]
     * @return [type]                    [description]
     */
    protected function compareGatewayAcquirer(Terminal\Entity $terminal): bool
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
        return ($terminal->isInternational() === $this->isInternational());
    }

    protected function getComparisonFunction(string $key)
    {
        return 'compare' . studly_case($key);
    }
}
