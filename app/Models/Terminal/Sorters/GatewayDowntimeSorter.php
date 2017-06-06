<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Gateway\Downtime;

/*
 * Documentation here :
 * https://docs.google.com/document/d/1bsx1t21Q_n5cQBnM_REyolGbn92Fzscu0LrRYiKqqsU/
 *
 */
class GatewayDowntimeSorter extends Terminal\Sorter
{
    protected $properties = [
        'downtime',
    ];

    /**
     * Sorts the terminals wrt the downtimes.
     *
     * The terminals whose gateways are down,
     * will be pushed to the bottom of the list.
     * Scenarios & cases are mentioned in the spec.
     *
     * There is no weightage given to the priority in which the
     * terminals are moved to the end of the list.
     *
     * @param $terminals array of Terminal\Entity
     * @param $input array
     * @return $sortedTerminals array of Terminal\Entity
     */
    public function downtimeSorter(array $terminals, array $input) : array
    {
        $downtimes = (new Downtime\Core)->getApplicableDowntimesForPayment($terminals,$input);

        $sortedTerminals = $this->sortTerminals($terminals, $downtimes);

        return $sortedTerminals;
    }

    /**
     * Performs sorting on relevant downtimes & given terminals
     *
     * Separates terminals into demotedTerminals/nonDemotedTerminals
     * Returns after merging demoted with nonDemoted terminals
     *
     * @param $terminals array
     * @param $downtimes array
     * @param array (sorted array of terminals)
     */
    protected function sortTerminals(array $terminals, $downtimes) : array
    {
        if (count($downtimes) === 0)
        {
            return $terminals;
        }

        $demotedTerminals = [];

        foreach ($terminals as $terminal)
        {
            if (in_array($terminal, $demotedTerminals) === true)
            {
                continue;
            }

            foreach ($downtimes as $downtime)
            {
                $demoteTerminal = $this->shouldDemoteTerminal($terminal, $downtime);

                if ($demoteTerminal === true)
                {
                    $demotedTerminals[] = $terminal;
                }
            }
        }

        $nonDemotedTerminals = array_diff($terminals, $demotedTerminals);

        return array_merge($nonDemotedTerminals, $demotedTerminals);
    }

    /**
     * Checks if priority of terminal should be demoted
     *
     * Compares on basis on terminal related data
     * 1. Terminal Id
     * 2. Terminal Gateway
     * 3. Downtime Gateway
     *
     * @param $terminal Terminal\Entity
     * @param $downtime Downtime
     * @return bool
     */
    protected function shouldDemoteTerminal(Terminal\Entity $terminal, Downtime\Entity $downtime) : bool
    {
        if ($terminal->getId() === $downtime->getTerminalId())
        {
            return true;
        }

        if (($terminal->getGateway() === $downtime->getGateway()) or
            ($downtime->getGateway() === Downtime\Entity::ALL))
        {
            return true;
        }

        return false;
    }
}
