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
    public function sharedSorter($terminals, array $input)
    {
        $sharedTerminals = [];
        $nonSharedTerminals = [];

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            if ($terminal->isShared() === true)
            {
                $sharedTerminals[] = $terminal;
            }
            else
            {
                $nonSharedTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge($nonSharedTerminals, $sharedTerminals);

        return $sortedTerminals;
    }
}
