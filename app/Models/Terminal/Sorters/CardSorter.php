<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Card;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Gateway\Priority as GatewayPriority;

class CardSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
        'emi',
    ];

    // Arrange card terminals in order of gateway
    public function gatewaySorter($terminals, bool $verbose = false)
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

        if ($verbose === true)
        {
            $this->trace->info(TraceCode::CARD_GATEWAY_PRIORITY, $gatewaysPriority);
        }

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

    public function emiSorter($terminals)
    {
        $bank = $this->input['payment']->getBank();

        // We need to sort only for Axis, since only for Axis do we need to
        // prioritize emi enabled terminal over card terminal
        if (($this->input['payment']->isMethod(Method::EMI) === false) || ($bank !== IFSC::UTIB))
        {
            return $terminals;
        }

        $orderedTerminals = $unorderedTerminals = [];

        foreach ($terminals as $terminal)
        {
            if (($terminal->isEmiEnabled() === true))
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
