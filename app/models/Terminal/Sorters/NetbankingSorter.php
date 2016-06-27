<?php

namespace Models\Terminal\Sorters;

use Models\Terminal;
use Models\Payment\Gateway;

class NetbankingSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    // Arrange netbanking terminals in the order
    // Direct bank first, next Direct gateway, finally shared
    // In This order as well use,
    public function gatewaySorter($terminals, $input)
    {
        $indexed = true;

        $bank = $input['payment']->getBank();

        $gatewaysForBank = Gateway::getGatewaysForNetbankingBank($bank, $indexed);

        $gatewaysPriority = Gateway::getGatewaysPriorityForNetbanking();

        $testTerminals = [];

        foreach ($gatewaysPriority as $gatewayType)
        {
            // First use the direct terminal
            if (($gatewayType === 'direct') and
                isset($gatewaysForBank['direct']))
            {
                $gateway = $gatewaysForBank['direct'];
            }
            else
            {
                $gateway = $gatewayType;
            }

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
