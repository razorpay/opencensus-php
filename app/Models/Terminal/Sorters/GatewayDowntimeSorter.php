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
     * Separates terminals into boosted/nonBoosted
     * Returns after merging boosted with nonBoosted terminals
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

        $boostedTerminals = [];

        $nonBoostedTerminals = [];

        foreach ($terminals as $terminal)
        {
            if ((in_array($terminal, $boostedTerminals) === true) or
                (in_array($terminal, $nonBoostedTerminals) === true))
            {
                continue;
            }

            foreach ($downtimes as $downtime)
            {
                $boostTerminal = $this->shouldBoostTerminal($terminal, $downtime);

                if ($boostTerminal === false)
                {
                    $nonBoostedTerminals[] = $terminal;
                }
                else
                {
                    $boostedTerminals[] = $terminal;
                }
            }
        }

        return array_merge($boostedTerminals, $nonBoostedTerminals);
    }

    /**
     * Checks if priority of terminal should be kept low
     *
     * Filters on basis on terminal related data
     * 1. Terminal Id
     * 2. Terminal Gateway
     * 3. Downtime Gateway
     *
     * @param $terminal Terminal\Entity
     * @param $downtime Downtime
     * @return bool
     */
    protected function shouldBoostTerminal(Terminal\Entity $terminal, Downtime\Entity $downtime) : bool
    {
        if ($terminal->getId() === $downtime->getTerminalId())
        {
            return false;
        }

        if (($terminal->getGateway() === $downtime->getGateway()) or
            ($downtime->getGateway() === Downtime\Entity::ALL))
        {
            return false;
        }

        return true;
    }
}
