<?php

namespace RZP\Models\Terminal;

use App;
use RZP\Models\Bank\IFSC;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;

class Binning
{
    /**
     * Define rules on terminal binning here
     */
    protected static $rules = [
        [
            'method'         => Method::NETBANKING,
            'binFor'         => Shared::BILLDESK_RAZORPAY_TERMINAL,
            'binWith'        => '59U9GqsARtkw2r',
            'bank'           => IFSC::KKBK,
            'load'           => 75,
        ]
    ];

    public function getRules()
    {
        return self::$rules;
    }

    /**
     * Used in the Terminal/Selector to select a terminal
     * if a binning rule is defined for the current case.
     *
     * @param  Entity $terminal
     * @param  array  $input
     * @param  int    $chancePercent Chance variable
     * @param  array  $terminals     Array of possible terminals
     * @return Entity
     */
    public function select($selectedTerminal, $chancePercent, $input, $terminals)
    {
        // Since only the first terminal would be selected
        // Check condition on binFor only on first terminal

        list($returnTlId, $rule) = $this->chooseTerminalWithRules($selectedTerminal, $chancePercent, $input);

        if (empty($rule) === false)
        {
            // If from the possible terminals the binWith is not found,
            // The originally selected terminal will be returned.
            foreach ($terminals as $terminal)
            {
                if ($terminal->getId() === $rule['binWith'])
                {
                    return $terminal;
                }
            }
        }

        return $selectedTerminal;
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
            if ($this->terminalExists($returnTlId))
            {
                $terminal = $this->terminal;
            }
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

    /**
     * Based on current terminal, rule, chance and input determines
     * if the current rule is applicable or not.
     */
    public function isRuleApplicable($terminal, $rule, $chancePercent, $input = [])
    {
        if ((isset($rule['method']) === true) and
            ($rule['method'] === $input['payment']->getMethod()))
        {
            $check = (($chancePercent <= $rule['load']) and
                      ($rule['binFor'] === $terminal->getId()));

            switch ($rule['method'])
            {
                case Method::NETBANKING:
                    if ($rule['bank'] === $input['payment']->getBank())
                    {
                        return $check;
                    }

                    break;

                case Method::CARD:
                case Method::EMI:
                    $network = $input['payment']->card->getNetworkCode();

                    if (Gateway::isCardNetworkSupported($network, $rule['binWithGateway']))
                    {
                        return $check;
                    }

                    break;

                case Method::WALLET:
                    return $check;
            }
        }

        return false;
    }

    /**
     * A repo function that return the terminal by identity
     */
    protected function terminalExists($terminal)
    {
        $app = App::getFacadeRoot();

        $this->terminal = $app['repo']->terminal->find($terminal);

        return $this->terminal;
    }
}
