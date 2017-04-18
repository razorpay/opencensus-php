<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Gateway\LoadRule;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class TerminalLoadSorter extends Terminal\Sorter
{
    protected $properties = [
        'gateway',
    ];

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
        if ($options === null)
        {
            return $terminals;
        }

        $merchantId = $input['merchant']->getId();

        $applicableRules = (new LoadRule\Core)->fetchApplicableRules($terminals, $input);

        // If no rules are present for load sorting we return the terminals list as is
        if ($applicableRules->isEmpty() === true)
        {
            return $terminals;
        }

        //
        // We match terminals to the eligible rules based on terminal criteria
        // and generate a map with the structure
        // [
        //      <terminal_id> => <load_value>
        // ]
        //
        $terminalLoadMap = (new LoadRule\Core)->matchTerminalToRule($terminals, $applicableRules);

        if (empty($terminalLoadMap) === true)
        {
            return $terminals;
        }

        // It can happen that the cumulative load across eligible terminaals
        // exceeds 10000. In that case, we normalize the load values
        // E.g Rule R1 - Load 7000
        // Rule R2 - Load 5000
        // After normalization relative load in probability space of 10000
        // will be R1 = 5833, R2 = 4166
        $this->checkAndBalanceLoad($terminalLoadMap);

        $chancePercent = $options->getChance();

        $boostedTerminals = [];

        $nonBoostedTerminals = [];

        $boostedTerminalIds = $this->getBoostedTerminalIds($terminalLoadMap, $chancePercent);

        if (empty($boostedTerminalIds) === false)
        {
            foreach ($terminals as $terminal)
            {
                if (in_array($terminal->getId(), $boostedTerminalIds, true))
                {
                    $boostedTerminals[] = $terminal;
                }
                else
                {
                    $nonBoostedTerminals[] = $terminal;
                }
            }

            $terminals = array_merge($boostedTerminals, $nonBoostedTerminals);
        }

        return $terminals;
    }

    protected function getBoostedTerminalIds(array $terminalLoadMap, int $chancePercent)
    {
        $boostedTerminalIds = [];

        $cumulativeProbabity = 0;

        foreach ($terminalLoadMap as $terminalId => $load)
        {
            $cumulativeProbabity += $load;

            // Checking > 100-p, rather than simply <p
            // because in test cases we're always setting
            // p to zero, to avoid unexpected behaviour.
            if ($chancePercent > (10000 - $cumulativeProbabity))
            {
                $boostedTerminalIds[] = $terminalId;
            }
        }

        return $boostedTerminalIds;
    }

    protected function getApplicableRules($terminals)
    {
        $allRules = $this->getRules();

        $applicableRules = [];

        foreach ($allRules as $rule)
        {
            // If the rule has any matching terminal then only merge
            // it to the applicableRules array
            $merge = false;

            // Rules only apply to terminals that have made it
            // this far in the selection process
            foreach ($terminals as $terminal)
            {
                if ($this->validateAttributes($rule['attributes'], $terminal))
                {
                    $rule['ids'][] = $terminal->getId();
                    $merge = true;
                }
            }

            if ($merge === true)
            {
                $applicableRules[] = $rule;
            }
        }

        return $applicableRules;
    }

    protected function validateAttributes($attributes, $terminal)
    {
        // Get all terminal attributes
        $termAttributes = $terminal->getAttributes();

        // Gets diff of the two. If there is any diff,
        // then attributes are not perfectly matching.
        return (count(array_diff_assoc($attributes, $termAttributes)) === 0);
    }

    protected function validateRules($cumulativeProbability, $applicableRules)
    {
        // Cumulative probability for all applicable rules
        // can't possibly be above 100. In this case, don't
        // boost any terminal.
        if ($cumulativeProbability > 100)
        {
            $this->trace->error(
                TraceCode::TERMINAL_BOOST_INVALID,
                [
                    'cumulative_probabity' => $cumulativeProbability,
                    'applicable_rules'     => $applicableRules,
                ]
            );

            return false;
        }

        return true;
    }
}
