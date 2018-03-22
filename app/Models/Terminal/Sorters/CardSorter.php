<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Method;
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

    // Arrange card terminals in order of gateway
    public function authTypeSorter($terminals)
    {
        // No need to sort unless the method is either card or EMI.
        if ($this->input['payment']->isMethodCardOrEmi() === false)
        {
            return $terminals;
        }

        $boostedTerminals = $unboostedTerminals = [];

        $authType = (array) $this->input['payment']->getAuthType();

        $preferredAuthentications = $this->input['metadata']->get('preferred_authentication', $authType);

        if (empty($preferredAuthentications) === true)
        {
            return $terminals;
        }

        $i = 0;

        foreach ($preferredAuthentications as $authType)
        {
            $i++;

            // As the terminals are from the priority list
            // append to the terminal
            foreach ($terminals as $terminal)
            {
                switch ($authType)
                {
                    case Payment\AuthType::PIN:
                        $boost = $terminal->isPinAuth();
                        break;

                    default:
                        $boost = false;
                        break;
                }

                if ($boost === true)
                {
                    $boostedTerminals[$i][] = $terminal;
                }
                else
                {
                    $unboostedTerminals[] = $terminal;
                }
            }
        }

        $boostedTerminals = call_user_func_array('array_merge', $boostedTerminals);

        return array_unique(array_merge($boostedTerminals, $unboostedTerminals));
    }
}
