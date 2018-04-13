<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\AuthType;
use RZP\Models\Gateway\Priority as GatewayPriority;

class CardSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
        'auth_type',
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

    // Arrange card terminals in order of preferred auth type
    public function authTypeSorter($terminals)
    {
        $payment = $this->input['payment'];

        $preferredAuthentications = $payment->getMetadata('preferred_auth');

        // No need to sort unless the method is either card or EMI.
        // or preferredAuthentications is empty.
        if (($payment->isMethodCardOrEmi() === false) or
            (empty($preferredAuthentications) === true))
        {
            return $terminals;
        }

        $orderedTerminals = [];
        $unorderedTerminals = $terminals;

        foreach ($preferredAuthentications as $authType)
        {
            // As the terminals are from the priority list
            // append to the terminal
            //
            $terminals = $unorderedTerminals;

            foreach ($terminals as $terminal)
            {

                if ($terminal->isAuthTypeEnabled($authType) === true)
                {
                    $orderedTerminals[] = $terminal;

                    unset($unorderedTerminals[$terminal]);
                }
            }
        }

        return $orderedTerminals;
    }
}
