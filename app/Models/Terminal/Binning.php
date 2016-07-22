<?php

namespace RZP\Models\Terminal;

use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Binning
{
    /**
     * Define rules on terminal binning here
     */
    protected static $rules = [
        [
        'binFor'      => Shared::HDFC_RAZORPAY_TERMINAL,
        'binWith'     => Shared::CYBERSOURCE_HDFC_TERMINAL,
        'loadPercent' => 1,
        ],
        [
        'binFor'      => Shared::BILLDESK_RAZORPAY_TERMINAL,
        'binWith'     => '59U9GqsARtkw2r',
        'loadPercent' => 10,
        'method'      => Method::NETBANKING,
        'bank'        => Gateway::NETBANKING_KOTAK,
        ]
    ];

    public function getRules()
    {
        return self::$rules;
    }

    public function isRuleApplicable($terminal, $rule, $chancePercent)
    {
        return (($chancePercent <= $rule['loadPercent']) and
                ($rule['binFor'] === $terminal->getId()));
    }

    /**
     * Used in the Terminal/Selector to select a terminal
     * if a binning rule is defined for the current case.
     *
     * @param  array    Array of possible terminals
     * @param  int      Chance Variable
     * @return terminal
     */
    public function select($terminals, $chancePercent, $input)
    {
        // Since only the first terminal would be selected
        // Check condition on binFor only on first terminal
        $checkTerminal = $terminals[0];

        list($returnTlId, $rule) = $this->chooseTerminalWithRules($checkTerminal, $chancePercent, $input);

        if (empty($rule) === false)
        {
            foreach ($terminals as $terminal)
            {
                if ($terminal->getId() === $rule['binWith'])
                {
                    return $terminal;
                }
            }
        }

        return $checkTerminal;
    }

    /**
     * Used in the TerminalPicker to pick a terminal if a
     * binning rule is defined for the current case
     *
     * @param  terminal
     * @param  int
     * @return terminal
     */
    public function pick($terminal, $chancePercent, $input)
    {
        list($returnTlId, $rule) = $this->chooseTerminalWithRules($terminal, $chancePercent, $input);

        // Fetch new terminal if some rule is to be applied.
        if (empty($rule) === false)
        {
            return $this->fetchTerminalById($returnTlId);
        }

        return $terminal;
    }

    public function chooseTerminalWithRules($terminal, $chancePercent, $input)
    {
        $rules = $this->getRules();

        foreach ($rules as $rule)
        {
            if ($this->isRuleApplicable($terminal, $rule, $chancePercent, $input))
            {
                return [$rule['binWith'], $rule];
            }
        }

        return [$terminal->getId(), null];
    }

    // Call to repo for picker
    protected function terminalExists($terminal)
    {
        $this->terminal = $this->repo->find($terminal);

        return $this->terminal;
    }
}
