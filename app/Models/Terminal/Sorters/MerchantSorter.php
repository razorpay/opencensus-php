<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class MerchantSorter extends Terminal\Sorter
{
    protected $properties = [
        'category',
    ];

    // Specific category terminals should be placed
    // above the generic category terminals
    // Place the terminals of the same category as the merchant above
    // generic terminals

    /**
     * Specific category terminals should be placed
     * above the generic category terminals.
     * Place the terminals of the same category as the merchant
     * above generic terminals.
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    public function categorySorter($terminals, array $input)
    {
        $merchantCategory = $input['merchant']->getCategory();

        $specificCategoryTerminals = [];
        $genericCategoryTerminals = [];

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            if ($terminal->getCategory() === $merchantCategory)
            {
                $specificCategoryTerminals[] = $terminal;
            }
            else
            {
                $genericCategoryTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge($specificCategoryTerminals, $genericCategoryTerminals);

        return $sortedTerminals;
    }
}
