<?php

namespace Models\Terminal\Sorters;

use Models\Terminal;

class MerchantSorter extends Terminal\Sorter
{
    protected $properties = [
        'category',
        'usedTerminal'
    ];

    // Use the terminals with the appropriate category first!
    // Push the others later
    public function categorySorter($terminals, $input)
    {
        // $newTerminals = $terminals;


        return $terminals;
    }

    // Terminals that have been used for this payment need to be sorted
    // placed much below in the the priority order.
    public function usedTerminalSorter($terminals, $input)
    {
        return $terminals;
    }
}
