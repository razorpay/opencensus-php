<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;

class TerminalBankFilter extends Terminal\Filter
{
    public function filter(array $terminals, $verbose = false)
    {
        if ($this->input['payment']->isNetbanking() === false)
        {
            return $terminals;
        }

        $applicableTerminals = [];

        $bank = $this->input['payment']->getBank();

        foreach ($terminals as $terminal)
        {
            $enabledBanks = (array) $terminal->getEnabledBanks();

            if (in_array($bank, $enabledBanks, true) === true)
            {
                $applicableTerminals[] = $terminal;
            }
        }

        if (($this->isLiveMode() === true) and (count($applicableTerminals) !== count($terminals)))
        {
            $terminalData = array_pluck($terminals, 'gateway', 'id');

            $applicableTerminalData = array_pluck($applicableTerminals, 'gateway', 'id');

            $this->trace->critical(TraceCode::TERMINAL_BANK_FILTER_DIFF, [
                'expected' => $terminalData,
                'actual'   => $applicableTerminalData,
                'bank'     => $bank,
            ]);
        }

        return $applicableTerminals;
    }
}
