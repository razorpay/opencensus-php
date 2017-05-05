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

        $chancePercent = $options->getChance();

        $boostedTerminals = $this->getBoostedTerminals($terminals, $applicableRules, $chancePercent);

        if (empty($boostedTerminals) === true)
        {
            return $terminals;
        }

        $nonBoostedTerminals = array_diff($terminals, $boostedTerminals);

        $terminals = array_merge($boostedTerminals, $nonBoostedTerminals);

        return $terminals;
    }

    /**
     * Matches terminals to a rule based on comparing terminal attributes to terminal
     * related rule attributes. Returns a map, mapping rule id's to terminals like
     * [
     *     <rule_id> => [<terminal_ids>]
     * ]
     *
     * @param  Base\PublicCollection $terminals collection of available terminals
     * @param  Base\PublicCollection $rules     collection of applicable rules
     * @return array                            map of rule_id => terminals
     */
    protected function getBoostedTerminals(array $terminals, Base\PublicCollection $rules, int $chancePercent): array
    {
       $totalLoad = 0;

       $boostedTerminals = [];

       foreach ($rules as $rule)
       {
            $totalLoad += $rule->getLoad();

            $matchingTerminals = [];

            foreach ($terminals as $terminal)
            {
                if ($rule->matches($terminal) === true)
                {
                    $matchingTerminals[] = $terminal;
                }
            }

            if ($totalLoad >= $chancePercent)
            {
                if (empty($matchingTerminals) === false)
                {
                    $this->traceBoostedTerminals($terminals, $chancePercent);

                    $boostedTerminals = $matchingTerminals;

                    break;
                }
            }
       }

       return $boostedTerminals;
    }
}
