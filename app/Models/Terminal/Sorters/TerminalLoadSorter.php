<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Terminal;
use RZP\Models\Payment\Gateway;
use RZP\Constants\Mode;

class TerminalLoadSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    protected static $rules = [
        '6UF3c6ZxiamtJA' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 5,
        ],
        // Test terminals, won't be used on prod
        '1000FrstDataTl' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 5,
        ],
        '1000CybrsTrmnl' => [
            'gateway'    => Gateway::CYBERSOURCE,
            'load'       => 5,
        ]
    ];

    public function getRules()
    {
        return self::$rules;
    }

    /**
     * Select terminals to be given preference
     * and give them a boost according to the
     * defined rules
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    public function gatewaySorter($terminals, array $input, $options)
    {
        $sortedTerminals = $terminals;

        if (is_null($options) === false)
        {
            $chancePercent = $options->getChance();

            $boostedTerminalId = $this->getBoostedTerminalId($terminals, $chancePercent);

            if (is_null($boostedTerminalId) == false)
            {
                foreach ($sortedTerminals as $key => $terminal)
                {
                    if ($terminal->getId() === $boostedTerminalId)
                    {
                        unset($sortedTerminals[$key]);

                        array_unshift($sortedTerminals, $terminal);
                    }
                }
            }
        }

        return $sortedTerminals;
    }

    protected function getBoostedTerminalId($terminals, $chancePercent)
    {
        // Not all rules will apply, a terminal may already have
        // been rejected in the previous sorting/filtering steps.
        $applicableRules = $this->getApplicableRules($terminals);

        $cumulativeProbabity = 0;

        foreach ($applicableRules as $terminalId => $rule)
        {
            $cumulativeProbabity += $rule['load'];

            $this->validateRules($cumulativeProbabity);

            // Checking >100-p, rather than simply <p
            // because in test cases we're always setting
            // p to zero, to avoid unexpected bheaviour.
            if ($chancePercent > (100 - $cumulativeProbabity))
            {
                return $terminalId;
            }
        }
    }

    protected function getApplicableRules($terminals)
    {
        $allRules = $this->getRules();

        $ruledTerminals = array_keys($allRules);

        $applicableRules = [];

        // Rules only apply to terminals that have made it
        // this far in the selection process
        foreach ($terminals as $terminal)
        {
            $terminalId = $terminal->getId();

            if (in_array($terminalId, $ruledTerminals) === true)
            {
                $applicableRules[$terminalId] = $allRules[$terminalId];
            }
        }

        return $applicableRules;
    }

    protected function validateRules($cumulativeProbabity)
    {
        // Cumulative probability for all applicable rules
        // can't possibly be above 100
        if ($cumulativeProbabity > 100)
        {
            throw new Exception\LogicException("Cumulative probability is " .
                            $cumulativeProbabity . ", shouldn't be above 100");
        }
    }
}
