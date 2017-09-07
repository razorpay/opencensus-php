<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class ExclusivitySorter extends Terminal\Sorter
{
    protected $properties = [
        'shared',
    ];

    /**
     * Direct terminals should be placed above the shared terminals.
     * Place the direct terminals above shared terminals.
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    public function sharedSorter($terminals)
    {
        $sharedTerminals1 = []; // List of shared terminals where we dont have direct terminals
        $sharedTerminals2 = []; // List of shared terminals where we have direct terminals

        $nonSharedTerminals = [];
        $nonSharedTerminalGateways = [];

        // find all non shared terminals
        foreach ($terminals as $terminal)
        {
            if ($terminal->isShared() === false)
            {
                $nonSharedTerminals[] = $terminal;

                $nonSharedTerminalGateways[] = $terminal->getGateway();
            }
        }

        // create 2 groups of shared terminals
        // 1. Terminals with gateways where we don't have direct terminals
        // 2. Terminals with gateways where we have direct terminals
        foreach ($terminals as $terminal)
        {
            if ($terminal->isShared() === true)
            {
                if (in_array($terminal->getGateway(), $nonSharedTerminalGateways, true))
                {
                    $sharedTerminals2[] = $terminal;
                }
                else
                {
                    $sharedTerminals1[] = $terminal;
                }

            }
        }

        $sortedTerminals = array_merge($nonSharedTerminals, $sharedTerminals1, $sharedTerminals2);

        return $sortedTerminals;
    }
}
