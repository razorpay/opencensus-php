<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Gateway;

class InternationalCardSorter extends Terminal\Sorter
{
    protected $properties = [
        'international_card',
    ];

    /**
     * This sorter applies only to international cards
     * For International cards, axis terminals will be boosted.
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    public function internationalCardSorter($terminals, array $input)
    {
        if (($input['payment']->isMethodCardOrEmi() === false) or
            ($input['payment']->card->isInternational() === false))
        {
            return $terminals;
        }

        $boostedGateways = [Gateway::AXIS_MIGS];

        $boostedTerminals = [];

        $nonBoostedTerminals = [];

        // As the terminals are from the priority list
        // append to the terminal
        foreach ($terminals as $terminal)
        {
            if (in_array($terminal->getGateway(), $boostedGateways))
            {
                $boostedTerminals[] = $terminal;
            }
            else
            {
                $nonBoostedTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge($boostedTerminals, $nonBoostedTerminals);

        return $sortedTerminals;
    }
}
