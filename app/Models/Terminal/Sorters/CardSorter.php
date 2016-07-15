<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class CardSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    // Arrange card terminals in the order
    public function gatewaySorter($terminals, $input)
    {
        $method = $input['payment']->getMethod();

        // No need unless doing for card
        if ($method !== Method::CARD)
        {
            return $terminals;
        }

        $gatewaysPriority = Gateway::getGatewaysPriority($method, $input['mode']);

        $testTerminals = [];

        foreach ($gatewaysPriority as $gateway)
        {
            // As the terminals are from the priority list
            // append to the terminal
            foreach ($terminals as $terminal)
            {
                if ($terminal->getGateway() === $gateway)
                {
                    $testTerminals[] = $terminal;
                }
            }
        }

        return $testTerminals;
    }

}
