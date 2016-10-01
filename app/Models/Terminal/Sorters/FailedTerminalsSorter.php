<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class FailedTerminalsSorter extends Terminal\Sorter
{
    protected $properties = [
        'failed',
    ];

    /**
     * Given a list of terminals to exclude, this sorter sorts the terminals in such a way
     * that the excluded terminals are put at the bottom and the non excluded ones are
     * put at the top
     * @param $terminals
     * @param $input
     */
    public function failedSorter($terminals, $input)
    {
        $sortedTerminals = $terminals;

        if (isset($input['failed_terminals']))
        {
            $nonFailedTerminals = [];

            $failedTerminals = [];

            $flipped = array_flip($input['failed_terminals']);

            foreach($terminals as $terminal)
            {
                $terminalId = $terminal->getId();

                if (isset($flipped[$terminalId]) === true)
                {
                    $failedTerminals[] = $terminal;
                }
                else
                {
                    $nonFailedTerminals[] = $terminal;
                }
            }

            $sortedTerminals = array_merge($nonFailedTerminals, $failedTerminals);
        }

        return $sortedTerminals;
    }
}