<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Models\Terminal;

class CapabilitySorter extends Terminal\Sorter
{
    protected $properties = [
        'capability',
    ];

    // @codingStandardsIgnoreLine
    public function capabilitySorter($terminals)
    {
        if ($this->input['payment']->isMethodCardOrEmi() === false)
        {
            return $terminals;
        }

        $orderedTerminals = $unorderedTerminals = [];

        foreach ($terminals as $terminal)
        {
            if ($terminal->getCapability() === Terminal\Capability::AUTHORIZE)
            {
                $orderedTerminals[] = $terminal;
            }
            else
            {
                $unorderedTerminals[] = $terminal;
            }
        }

        return array_merge($orderedTerminals, $unorderedTerminals);
    }
}
