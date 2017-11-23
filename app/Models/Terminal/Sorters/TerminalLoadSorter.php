<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Base;
use RZP\Models\Gateway\Rule;
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
     * @return array
     */
    public function gatewaySorter($terminals)
    {
        if (empty($this->rules) === true)
        {
            return $terminals;
        }

        try
        {
            // @note: Temporarily setting verbose to true here for logging of terminal
            // sorting using rules
            $verbose = true;

            $this->traceSorterRules($verbose);

            $chancePercent = $this->options->getChance();

            $boostedTerminals = [];

            //
            // Here we have grouped the rules by order of specificity score, and we
            // iterate through the groups of rules in order of score (highest -> lowest).
            //
            foreach ($this->rules as $score => $rules)
            {
                $boostedTerminals = $this->getBoostedTerminals(
                                            $terminals,
                                            $chancePercent,
                                            $rules,
                                            $verbose);

                //
                // If we are able to get boosted teerminals at any specificity level
                // we select those and dont consider other rules with lower specificity
                // score
                //
                if (empty($boostedTerminals) === false)
                {
                    break;
                }
            }

            if (empty($boostedTerminals) === true)
            {
                return $terminals;
            }

            //
            // Puts any terminals which are not in boostedTerminals and puts them behind
            // the boosted terminals
            //
            $nonBoostedTerminals = array_diff($terminals, $boostedTerminals);

            $terminals = array_merge($boostedTerminals, $nonBoostedTerminals);

            return $terminals;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return $terminals;
        }
    }

    /**
     * Matches terminals to rules based on comparison of rule attributes and terminal attributes
     * If the rule load is selected as per the random chance percent value, the matching
     * terminals to that rule (if any) are boosted over other terminals
     *
     * @param  Base\PublicCollection $terminals     collection of available terminals
     * @param  int                   $chancePercent randomly selected chance value
     *                                              (between 0 - 10000)
     * @param  Base\PublicCollection $rules         collection of applicable rules
     *
     * @return array                            map of rule_id => terminals
     */
    protected function getBoostedTerminals(
                            array $terminals,
                            int $chancePercent,
                            Base\PublicCollection $rules,
                            bool $verbose = false)
    {
       $totalLoad = 0;

       foreach ($rules as $rule)
       {
            $totalLoad += $rule->getLoad();

            //
            // If the load so far is less than the random chance value, we dont
            // consider matching terminals with the rule, and move on to the next
            // rule if any.
            //
            if ($totalLoad < $chancePercent)
            {
                continue;
            }

            $boostedTerminals = [];

            foreach ($terminals as $terminal)
            {
                if ($rule->matches($terminal, $this->input['merchant']) === true)
                {
                    $boostedTerminals[] = $terminal;
                }
            }

            //
            // We iterate through the rules and keep adding the rule load to the
            // total load  value.  If the total  load is greater than chance
            // percentage, that rule  is selected.  For  e.g  if we have
            // rules R1 - load 30, and R2 load 50. If chance percentage is 40 in
            // the second iteration totalLoad becomes 80  > 40 and we select R2.
            // However if say the chance percentage was 90, then even  after all
            // iterations  totalLoad will  be 80 which is less than 90 and so no
            // rules will be selected
            //
            if (empty($boostedTerminals) === false)
            {
                $this->traceBoostedTerminals($boostedTerminals, $chancePercent, $verbose);
            }

            return $boostedTerminals;
       }

       return [];
    }

    protected function traceSorterRules(bool $verbose = false)
    {
        if ($verbose === true)
        {
            $ruleIds = [];

            foreach ($this->rules as $score => $rules)
            {
                $ruleIds = array_merge($ruleIds, $rules->pluck(Rule\Entity::ID)->toArray());
            }

            $this->trace->info(
                TraceCode::GATEWAY_SORTER_RULES,
                [
                    'rule_ids'       => $ruleIds,
                    'chance_percent' => $this->options->getChance(),
                ]);
        }
    }

    protected function traceBoostedTerminals(array $terminals, int $chancePercent, bool $verbose = false)
    {
        if ($verbose === true)
        {
            $traceData = [];

            $traceData['chance_percent'] = $chancePercent;

            $terminalIds = array_pluck($terminals, 'id');

            $traceData['boosted_terminals'] = $terminalIds;

            $this->trace->info(TraceCode::GATEWAY_LOAD_SORTING_BOOSTED_TERMINALS, $traceData);
        }
    }
}
