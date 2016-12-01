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

        $methodsAllowed = [Method::CARD, Method::EMI];

        // No need to sort unless the method is either card or EMI.
        if (in_array($method, $methodsAllowed, true) === false)
        {
            return $terminals;
        }

        // Fetch priority for card in case of card or emi
        $method = Method::CARD;

        $gatewaysPriority = Gateway::getGatewaysPriority($method, $input['mode']);

        $sortedTerminals = [];

        foreach ($gatewaysPriority as $gateway)
        {
            // As the terminals are from the priority list
            // append to the terminal
            foreach ($terminals as $terminal)
            {
                if ($terminal->getGateway() === $gateway)
                {
                    $sortedTerminals[] = $terminal;
                }
            }
        }

        return $sortedTerminals;
    }
}
