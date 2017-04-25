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
        // We match terminals to a rule based on terminal criteria
        // and generate a map with the structure
        // [
        //      <rule_id> => [<terminal_ids>]
        // ]
        //
        $ruleToTerminalsMap = (new LoadRule\Core)->matchTerminalsToRule($terminals, $applicableRules);

        if (empty($ruleToTerminalsMap) === true)
        {
            return $terminals;
        }

        // It can happen that the cumulative load across eligible terminaals
        // exceeds 10000. In that case, we normalize the load values
        // E.g Rule R1 - Load 7000
        // Rule R2 - Load 5000
        // After normalization relative load in probability space of 10000
        // will be R1 = 5833, R2 = 4166
        $this->checkAndBalanceLoad($applicableRules);

        $chancePercent = $options->getChance();

        $boostedTerminals = $this->getBoostedTerminalIds($ruleToTerminalsMap, $applicableRules, $chancePercent);

        $boostedTerminalIs = $this->getBoostedTerminals(
                                                        $ruleToTerminalsMap,
                                                        $applicableRules,
                                                        $chancePercent);

        // If no terminals are boosted as per the random selection return the set
        // of all terminals
        if (empty($boostedTerminals) === true)
        {
            return $terminals;
        }

        $nonBoostedTerminals = array_diff($terminals, $boostedTerminals);

        $terminals = array_merge($boostedTerminals, $nonBoostedTerminals);

        return $terminals;
    }

    protected function getBoostedTerminals(
                                            array $ruleToTerminalsMap,
                                            Base\PublicCollection $applicableRules,
                                            int $chancePercent)
    {
        $totalLoad = 0;

        foreach ($rule as $ruleId => $terminals)
        {
            $rule = $applicableRules->search(function ($item) use ($ruleId)
            {
                return ($item->getId() === $ruleId);
            });

            $load = $rule->getLoad();

            $totalLoad += $load;

            if ($totalLoad >= $chancePercent)
            {
                return $terminals;
            }
        }
    }

    protected function checkAndBalanceLoad(Base\PublicCollection $applicableRules)
    {
        ;
    }
}
