<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Exception;

class TerminalLoadSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

    protected static $rules = [
        '6UF3c6ZxiamtJA' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 0,
        ],

        '1000AxisMigsTl' => [
            'gateway'    => Gateway::AXIS_MIGS,
            'advance'    => [
                'method'  => 'card',
                'type'    => 'debit'
            ],
            'load'       => 0,
        ],

        // Test terminals, won't be used on prod.
        //
        // Because of the way applicableRules are computed, the terminals
        // in this list are assigned chance ranges in ascending order,
        // i.e. here 1000FrstDataTl will be selected for chance = 86=>90,
        // while 1000CybrsTrmnl will be selected for chance = 91->100.

        '1000FrstDataTl' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 5,
        ],
        '1000CybrsTrmnl' => [
            'gateway'    => Gateway::CYBERSOURCE,
            'load'       => 10,
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

            $boostedTerminalId = $this->getBoostedTerminalId($input, $terminals, $chancePercent);

            if (is_null($boostedTerminalId) == false)
            {
                foreach ($sortedTerminals as $key => $terminal)
                {
                    if ($terminal->getId() === $boostedTerminalId)
                    {
                        unset($sortedTerminals[$key]);

                        array_unshift($sortedTerminals, $terminal);

                        break;
                    }
                }
            }
        }

        return $sortedTerminals;
    }

    protected function getBoostedTerminalId($input, $terminals, $chancePercent)
    {
        // Not all rules will apply, a terminal may already have
        // been rejected in the previous sorting/filtering steps.
        $applicableRules = $this->getApplicableRules($terminals);

        $cumulativeProbabity = 0;

        foreach ($applicableRules as $terminalId => $rule)
        {
            $cumulativeProbabity += $rule['load'];

            $this->validateRules($cumulativeProbabity);

            $advanceCheck = true;

            if (isset($rule['advance']) === true)
            {
                $advanceCheck = $this->applyAdvancedRules($input, $rule);
            }

            // Checking >100-p, rather than simply <p
            // because in test cases we're always setting
            // p to zero, to avoid unexpected bheaviour.
            if (($advanceCheck === true) and
                ($chancePercent > (100 - $cumulativeProbabity)))
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

            if (in_array($terminalId, $ruledTerminals, true) === true)
            {
                $applicableRules[$terminalId] = $allRules[$terminalId];
            }
        }

        return $applicableRules;
    }

    protected function applyAdvancedRules(array $input, array $rule)
    {
        if (isset($rule['advance']['method']) === true)
        {
            switch ($rule['advance']['method'])
            {
                case Method::CARD:

                    if (isset($rule['advance']['type']) === true)
                    {
                        $cardType = $input['payment']->card->getType();

                        if ($rule['advance']['type'] === $cardType)
                        {
                            return true;
                        }

                        return false;
                    }

                    break;
            }
        }

        return true;
    }

    protected function validateRules($cumulativeProbability)
    {
        // Cumulative probability for all applicable rules
        // can't possibly be above 100
        if ($cumulativeProbability > 100)
        {
            throw new Exception\LogicException("Cumulative probability is " .
                $cumulativeProbability . ", shouldn't be above 100");
        }
    }
}
