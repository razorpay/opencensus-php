<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Constants\Mode;
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
        // i.e. here 1000FrstDataTl will be selected for chance = 86->90,
        // while 1000CybrsTrmnl will be selected for chance = 91->100.

        '1000FrstDataTl' => [
            'gateway'    => Gateway::FIRST_DATA,
            'load'       => 5,
        ],
        '1000CybrsTrmnl' => [
            'gateway'    => Gateway::CYBERSOURCE,
            'load'       => 10,
        ],
    ];

    protected static $prodTrialGateways = [
        Gateway::NETBANKING_ICICI,
    ];

    protected static $testTrialGateways = [
        Gateway::NETBANKING_ICICI,
    ];

    public function getRules()
    {
        return self::$rules;
    }

    public function getTrialGateways()
    {
        if ($this->mode === Mode::TEST)
        {
            return self::$testTrialGateways;
        }

        return self::$prodTrialGateways;
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
        $applicableGateways = $this->getApplicableGateways($terminals);

        $sortedTerminals = [];

        foreach ($terminals as $key => $terminal)
        {
            if (in_array($terminal->getGateway(), $applicableGateways))
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

        if (is_null($options) === false)
        {
            $chancePercent = $options->getChance();

            $boostedTerminalId = $this->getBoostedTerminalId(
                $terminals, $chancePercent);

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
            // p to zero, to avoid unexpected behaviour.
            if ($chancePercent > (100 - $cumulativeProbabity))
            {
                return $terminalId;
            }
        }
    }

    // Get all the gateways applicable to this
    protected function getApplicableGateways($terminals)
    {
        $allTrialGateways = $this->getTrialGateways();

        $applicableGateways = [];

        foreach ($terminals as $terminal)
        {
            $terminalGateway = $terminal->getGateway();

            if (in_array($terminalGateway, $allTrialGateways, true) === true)
            {
                $applicableGateways[] = $terminalGateway;
            }
        }

        return $applicableGateways;
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
