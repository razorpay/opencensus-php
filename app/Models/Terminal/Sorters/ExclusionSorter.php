<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class ExclusionSorter extends Terminal\Sorter
{
    protected $properties = [
        'exclude',
    ];

    /**
     * Given a list of terminals to exclude, this sorter sorts the terminals in such a way
     * that the excluded terminals are put at the bottom and the non excluded ones are
     * put at the top
     * @param $terminals
     * @param $input
     */
    public function excludeSorter($terminals, $input)
    {
        $sortedTerminals = $terminals;

        if (isset($input['exclude']))
        {
            $nonExcluded = [];

            $excluded = [];

            $flipped = array_flip($input['exclude']);

            foreach($terminals as $terminal)
            {
                $terminalId = $terminal->getId();

                if (isset($flipped[$terminalId]) === true)
                {
                    $excluded[] = $terminal;
                }
                else
                {
                    $nonExcluded[] = $terminal;
                }
            }
            $sortedTerminals = array_merge($nonExcluded, $excluded);
        }
        return $sortedTerminals;
    }
}