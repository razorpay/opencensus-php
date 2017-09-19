<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Base\PublicCollection;

class FailedTerminalsSorter extends Terminal\Sorter
{
    protected $properties = [
        'failed',
    ];

    /**
     * Given a list of terminals to exclude, this sorter sorts the terminals in
     * such a way that the excluded terminals are put at the bottom and
     * the non excluded ones are put at the top
     * @param $terminals
     * @param $input
     */
    public function failedSorter($terminals)
    {
        $sortedTerminals = $terminals;

        if (empty($this->options->getFailedTerminals() === false))
        {
            $nonFailedTerminals = [];

            $failedTerminals = [];

            $failedTerminalIds = $this->options->getFailedTerminals();

            // Flipping converts array into assoc array which has the keys indexed
            $flipped = array_flip($failedTerminalIds);

            foreach ($terminals as $terminal)
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
