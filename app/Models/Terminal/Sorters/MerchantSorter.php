<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class MerchantSorter extends Terminal\Sorter
{
    protected $properties = [
        'category',
    ];

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
        $specificCategoryTerminals = [];
        $genericCategoryTerminals  = [];
        $nonCategoryTerminals      = [];

        $method = $input['payment']->getMethod();

        $network = $input['payment']->isMethodCardOrEmi() ? $input['payment']->card->getNetworkCode() : null;

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            $merchantTerminalCategory = $input['merchant']->getTerminalCategoryForMethodAndNetwork($method, $network);

            $defaultCategory  = Terminal\Category::getDefaultForMethodAndNetwork($method, $network);

            $terminalCategory = $terminal->getTerminalCategory();

            if ($merchantTerminalCategory === $terminalCategory)
            {
                $specificCategoryTerminals[] = $terminal;
            }
            else if ($defaultCategory === $terminalCategory)
            {
                $genericCategoryTerminals[] = $terminal;
            }
            else if (empty($terminalCategory) === true)
            {
                $nonCategoryTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge($specificCategoryTerminals, $genericCategoryTerminals, $nonCategoryTerminals);

        return $sortedTerminals;
    }
}
