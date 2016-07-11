<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

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
        $method = $input['payment']->getMethod();

        // No need unless doing for netbanking
        if ($method !== Method::NETBANKING)
        {
            return $terminals;
        }

        $indexed = true;

        $bank = $input['payment']->getBank();

        $gatewaysForBank = Gateway::getGatewaysForNetbankingBank($bank, $indexed);

        $context = ['merchant' => $input['merchant']->getId(), 'bank' => $bank];

        $gatewaysPriority = Gateway::getGatewaysPriority($method, $input['mode'], $context);

        $testTerminals = [];

        foreach ($gatewaysPriority as $gatewayType)
        {
            // First use the direct terminal
            if (($gatewayType === 'direct') and
                (isset($gatewaysForBank['direct'])))
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
