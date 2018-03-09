<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;

class RecurringSorter extends Terminal\Sorter
{
    protected $properties = [
        'fallback',
    ];

    // Arrange card terminals in order of gateway
    public function fallbackSorter($terminals)
    {
        $payment = $this->input['payment'];

        // gateway_tokens is set only if it's a recurring payment
        $gatewayTokens = $this->input['gateway_tokens'] ?? [];

        if (($payment->isSecondRecurring(true, $gatewayTokens) === false) or
            ($payment->isCard() === false))
        {
            return $terminals;
        }

        $gatewayTokenTerminals = [];
        $fallbackTerminals = [];

        $terminalCore = (new Terminal\Core);

        foreach ($terminals as $terminal)
        {
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
