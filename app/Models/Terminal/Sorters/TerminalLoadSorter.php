<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Exception;

class TerminalLoadSorter extends Terminal\Sorter
{
    // New sorter for trial gateways
    // add it here to the rules

    protected $properties = [
        'trial_gateway',
        'gateway',
    ];

    protected static $rules = [
        '6UF3c6ZxiamtJA' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 0,
        ],

        '1000AxisMigsTl' => [
            'gateway'    => Gateway::AXIS_MIGS,
            'load'       => 5,
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
        ],
        '100NbIciciTmnl' => [
            'gateway'    => Gateway::NETBANKING_ICICI,
            'load'       => 10, // 20-30 it gets a boost
        ]
    ];

    protected static $trialGatewayRules = [
        '100NbIciciTmnl' => [
            'gateway'    => Gateway::NETBANKING_ICICI,
        ]
    ];

    public function getRules()
    {
        return self::$rules;
    }

    public function getTrialRules()
    {
        return self::$trialGatewayRules;
    }

    /**
     * Gateways on trial mode get pushed
     * right to the bottom
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    public function trialGatewaySorter($terminals, array $input, $options)
    {
        $allRules = $this->getTrialRules();

        $applicableRules = $this->getApplicableRules($terminals, $allRules);

        $sortedTerminals = [];

        foreach ($terminals as $key => $terminal)
        {
            if (array_key_exists($terminal->getId(), $applicableRules))
            {
                $sortedTerminals[] = $terminal;

                // If on trial, remove key from terminals
                unset($terminals[$key]);
            }
        }

        $terminals = array_merge($terminals, $sortedTerminals);

        return $terminals;
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

        $allRules = $this->getRules();

        if (is_null($options) === false)
        {
            $chancePercent = $options->getChance();

            $boostedTerminalId = $this->getBoostedTerminalId(
                $terminals, $chancePercent, $allRules);

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

    /**
     * Select terminals to be given preference
     * and give them a boost according to the
     * defined rules
     *
     * @param $terminals
     * @param array $input
     * @return array
     */
    /*public function trialGatewaySorter($terminals, array $input, $options)
    {
        $sortedTerminals = $terminals;

        if (is_null($options) === false)
        {
            $chancePercent = $options->getChance();

            $boostedTerminalId = $this->getBoostedTerminalId($terminals, $chancePercent);
        }
    }*/

    protected function getBoostedTerminalId($terminals, $chancePercent, $allRules)
    {
        // Not all rules will apply, a terminal may already have
        // been rejected in the previous sorting/filtering steps.
        $applicableRules = $this->getApplicableRules($terminals, $allRules);

        $cumulativeProbabity = 0;

        foreach ($applicableRules as $terminalId => $rule)
        {
            $cumulativeProbabity += $rule['load'];

            $this->validateRules($cumulativeProbabity);

            // Checking >100-p, rather than simply <p
            // because in test cases we're always setting
            // p to zero, to avoid unexpected behaviour.
            if ($chancePercent > (100 - $cumulativeProbabity))
            {
                return $terminalId;
            }
        }
    }

    // Made this more generic for all rules
    protected function getApplicableRules($terminals, $allRules)
    {
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
