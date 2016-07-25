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
        if (in_array($method, $methodsAllowed) === false)
        {
            return $terminals;
        }

        // Fetch priority for card in case of card or emi
        $method = Method::CARD;

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

        return collect($testTerminals);

        // $terminals->sort(function($firstTerminal, $secondTerminal) use ($gatewaysPriority) {
        //     $firstGateway = $firstTerminal->getGateway;
        //     $secondGateway = $secondTerminal->getGateway;
        //
        //     if ($firstGateway == $secondGateway)
        //     {
        //         return 0;
        //     }
        //
        //     $firstGatewayLoc = array_search($firstGateway, $gatewaysPriority);
        //     $secondGatewayLoc = array_search($secondGateway, $gatewaysPriority);
        //
        //     if ($firstGatewayLoc === false)
        //     {
        //         return -1;
        //     }
        //
        //     if ($secondGatewayLoc === false)
        //     {
        //         return 1;
        //     }
        //
        //     return ($firstGatewayLoc < $secondGatewayLoc) ? 1 : -1;
        // });
        //
        // return $terminals;
    }
}
