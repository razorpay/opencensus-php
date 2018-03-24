<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal\Category;

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
     *
     * @return array
     */
    public function categorySorter($terminals)
    {
        $specificCategoryTerminals = [];

        $genericCategoryTerminals  = [];

        $nonCategoryTerminals      = [];

        $method = $this->input['payment']->getMethod();

        $category2 = $this->input['merchant']->getCategory2();

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            $gateway = $terminal->getGateway();

            $defaultCategory = Category::getDefaultForMethodAndGateway($method, $gateway);

            $merchantTerminalCategory = Category::getCategoryForMethodAndGateway($method, $gateway, $category2);

            $terminalCategory = $terminal->getNetworkCategory();

            if ($merchantTerminalCategory === $terminalCategory)
            {
                $specificCategoryTerminals[] = $terminal;
            }
            else if ($defaultCategory === $terminalCategory)
            {
                $genericCategoryTerminals[] = $terminal;
            }
            else
            {
                $nonCategoryTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge(
                                $specificCategoryTerminals,
                                $genericCategoryTerminals,
                                $nonCategoryTerminals);

        return $sortedTerminals;
    }
}
