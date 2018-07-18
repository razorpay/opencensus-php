<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class RecurringSorter extends Terminal\Sorter
{
    protected $properties = [
        'fallback',
    ];

    public function fallbackSorter($terminals)
    {
        $payment = $this->input['payment'];

        // gateway_tokens is set only if it's a recurring payment
        $gatewayTokens = $this->input['gateway_tokens'];

        //
        // We do the fallback stuff only for second recurring + card payments.
        //
        if (($payment->isSecondRecurring() === false) or
            ($payment->isCard() === false))
        {
            return $terminals;
        }

        $gatewayTokenTerminals = [];
        $fallbackTerminals = [];

        $terminalCore = (new Terminal\Core);

        foreach ($terminals as $terminal)
        {
            //
            // If not a fallback terminal, we would ALWAYS have a gateway token.
            // This is ensured by `recurringFilter` function.
            //
            if ($terminalCore->hasApplicableGatewayTokens($terminal, $payment, $gatewayTokens) === true)
            {
                $gatewayTokenTerminals[] = $terminal;
            }
            else
            {
                $fallbackTerminals[] = $terminal;
            }
        }

        $sortedTerminals = array_merge($gatewayTokenTerminals, $fallbackTerminals);

        return $sortedTerminals;
    }
}
