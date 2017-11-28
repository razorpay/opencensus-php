<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Gateway\Priority as GatewayPriority;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class CardSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    // Arrange card terminals in order of gateway
    public function gatewaySorter($terminals)
    {
        // No need to sort unless the method is either card or EMI.
        if ($this->input['payment']->isMethodCardOrEmi() === false)
        {
            return $terminals;
        }

        // Fetch priority for card in case of card or emi
        $method = Method::CARD;

        $gatewaysPriority = (new GatewayPriority\Core)
                            ->getGatewaysForMethod($method);

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
