<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Base;
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

        // TODO: Temporarily setting verbose to true here
        $verbose = true;

        $loadRuleCore = new LoadRule\Core;

        $applicableRules = $loadRuleCore->fetchApplicableRules($terminals, $input, $verbose);

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
        $ruleToTerminalsMap = $loadRuleCore->matchTerminalsToRule($terminals, $applicableRules, $verbose);

        // If no terminals are found matching the rules return the original set of
        // terminals
        if (empty($ruleToTerminalsMap) === true)
        {
            return $terminals;
        }

        // Filter out rules for which we didn't get any matching terminals
        $applicableRules = $applicableRules->filter(function ($rule) use ($ruleToTerminalsMap)
        {
            $selectedRuleIds = array_keys($ruleToTerminalsMap);

            return (in_array($rule->getId(), $selectedRuleIds, true) === true);
        });

        // TODO: Discuss on this once, don't think it is required as we are already
        // having strict checks during rule creation which should not allow such cases
        // It can happen in certain cases that the total load across all rules
        // exceeds 10000. In that case, we normalize the load values
        // E.g Rule R1 - Load 7000
        // Rule R2 - Load 5000
        // After normalization relative load in probability space of 10000
        // will be R1 = 5833, R2 = 4166
        // $loadRuleCore->checkAndBalanceLoads($applicableRules);

        $chancePercent = $options->getChance();

        $boostedTerminals = $this->getBoostedTerminals(
                                                        $ruleToTerminalsMap,
                                                        $applicableRules,
                                                        $chancePercent,
                                                        $verbose);

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
                                            int $chancePercent,
                                            bool $verbose = false)
    {
        $totalLoad = 0;

        foreach ($ruleToTerminalsMap as $ruleId => $terminals)
        {
            $index = $applicableRules->search(function ($item) use ($ruleId)
            {
                return ($item->getId() === $ruleId);
            });

            $rule = $applicableRules->get($index);

            $load = $rule->getLoad();

            $totalLoad += $load;

            if ($totalLoad >= $chancePercent)
            {
                $this->traceBoostedTerminals($terminals, $chancePercent, $verbose);

                return $terminals;
            }
        }
    }

    protected function traceBoostedTerminals(array $terminals, int $chancePercent, bool $verbose = false)
    {
        if ($verbose === true)
        {
            $traceData = [];

            $traceData['chance_percent'] = $chancePercent;

            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $traceData['boosted_terminals'] = $boostedTerminals;

            $this->trace->info(TraceCode::GATEWAY_LOAD_SORTING_BOOSTED_TERMINALS, $traceData);
        }
    }
}
