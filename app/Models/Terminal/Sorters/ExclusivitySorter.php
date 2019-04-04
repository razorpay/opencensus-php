<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class ExclusivitySorter extends Terminal\Sorter
{
    protected $properties = [
        'shared',
    ];

    /**
     * Sorts terminals so that direct terminals get preference over shared terminals
     * The sorted terminals have the following order
     * 1. Direct terminals
     * 2. Shared terminals whose gateways are not in the list of direct terminals
     * 3. Other gateway shared terminals
     *
     * @param $terminals
     *
     * @return array
     */
    public function sharedSorter($terminals)
    {
        // Shared terminals whose gateways don't have any direct terminals in the list of terminals
        $exclusiveSharedTerminals = [];

        // Shared terminals whose gateways also have a direct terminal in the list of terminals
        $nonExclusiveSharedTerminals = [];

        $directTerminals = [];
        $directTerminalGateways = [];

        foreach ($terminals as $terminal)
        {
            if ($terminal->isDirectForMerchant() === true)
            {
                $directTerminals[] = $terminal;

                $directTerminalGateways[] = $terminal->getGateway();
            }
        }

        // create 2 groups of shared terminals
        // 1. Terminals with gateways where we don't have direct terminals
        // 2. Terminals with gateways where we have direct terminals
        foreach ($terminals as $terminal)
        {
            if ($terminal->isDirectForMerchant() === false)
            {
                if (in_array($terminal->getGateway(), $directTerminalGateways, true) === true)
                {
                    $nonExclusiveSharedTerminals[] = $terminal;
                }
                else
                {
                    $exclusiveSharedTerminals[] = $terminal;
                }
            }
        }

        $sortedTerminals = array_merge($directTerminals,
                                      $exclusiveSharedTerminals,
                                      $nonExclusiveSharedTerminals);

        return $sortedTerminals;
    }
}
