<?php

namespace Models\Terminal\Sorters;

use Models\Terminal;

class MerchantSorter extends Terminal\Sorter
{
    protected $properties = [
        'category',
    ];

    // Specific category terminals should be placed
    // above the generic category terminals
    // Place the terminals of the same category as the merchant above
    // generic terminals
    public function categorySorter($terminals, $input)
    {
        $merchantCategory = $input['merchant']->getCategory();

        $testTerminals = [
            'specific' => null,
            'generic'  => null,
        ];

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            if ($terminal->getCategory() === $merchantCategory)
            {
                $testTerminals['specific'][] = $terminal;
            }
            else
            {
                $testTerminals['generic'][] = $terminal;
            }
        }

        $returnTerminals = [];

        if (isset($testTerminals['specific']))
        {
            foreach ($testTerminals['specific'] as $terminal)
            {
                $returnTerminals[] = $terminal;
            }
        }

        if (isset($testTerminals['generic']))
        {
            foreach ($testTerminals['generic'] as $terminal)
            {
                $returnTerminals[] = $terminal;
            }
        }

        return $returnTerminals;
    }
}
