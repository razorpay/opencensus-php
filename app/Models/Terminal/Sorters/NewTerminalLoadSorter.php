<?php

namespace RZP\Models\Terminal\Sorters;

use RZP\Models\Base;
use RZP\Models\Feature;
use RZP\Models\Gateway\Rule;
use RZP\Models\Merchant\Account;
use RZP\Models\Payment\Gateway;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class NewTerminalLoadSorter extends Terminal\Sorter
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

        $merchant = $input['merchant'];

        if ($merchant->isFeatureEnabled(Feature\Constants::NEW_LOAD_SORTING) === false)
        {
            return $this->fallbackLoadSorter($terminals, $input, $options);
        }

        try
        {
            // @note: Temporarily setting verbose to true here for logging of terminal
            // sorting using rules
            $verbose = true;

            $ruleCore = new Rule\Core;

            $applicableRules = $ruleCore->fetchApplicableRulesForPayment($terminals, $input);

            if ($verbose === true)
            {
                $this->trace->info(
                    TraceCode::GATEWAY_RULES_POST_FILTER,
                    [
                        'rules'          => $applicableRules->pluck(Rule\Entity::ID)->toArray(),
                        'chance_percent' => $options->getChance(),
                    ]);
            }

            // If no rules are present for load sorting we return the terminals list as is
            if ($applicableRules->isEmpty() === true)
            {
                return $terminals;
            }

            $chancePercent = $options->getChance();

            $boostedTerminals = $this->getBoostedTerminals(
                                            $terminals,
                                            $applicableRules,
                                            $chancePercent,
                                            $verbose);

            if (empty($boostedTerminals) === true)
            {
                return $terminals;
            }

            // Puts any terminals which are not in boostedTerminals and puts them behind
            // the boosted terminals
            $nonBoostedTerminals = array_diff($terminals, $boostedTerminals);

            $terminals = array_merge($boostedTerminals, $nonBoostedTerminals);

            return $terminals;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            return $this->fallbackLoadSorter($terminals, $input, $options);
        }
    }

    protected function fallbackLoadSorter($terminals, array $input, $options)
    {
        $this->trace->info(TraceCode::GATEWAY_LOAD_SORTING_FALLBACK);

        $terminals = (new TerminalLoadSorter)->sort($terminals, $input, false, $options);

        return $terminals;
    }

    /**
     * Matches terminals to rules based on comparison of rule attributes and terminal attributes
     * If the rule load is selected as per the random chance percent value, the matching
     * terminals to that rule (if any) are boosted over other terminals
     *
     * @param  Base\PublicCollection $terminals     collection of available terminals
     * @param  Base\PublicCollection $rules         collection of applicable rules
     * @param  int                   $chancePercent randomly selected chance value
     *                                              (between 0 - 10000)
     * @return array                            map of rule_id => terminals
     */
    protected function getBoostedTerminals(
                            array $terminals,
                            Base\PublicCollection $rules,
                            int $chancePercent,
                            bool $verbose = false)
    {
       $totalLoad = 0;

       foreach ($rules as $rule)
       {
            $totalLoad += $rule->getLoad();

            $boostedTerminals = [];

            foreach ($terminals as $terminal)
            {
                if ($rule->matches($terminal) === true)
                {
                    $boostedTerminals[] = $terminal;
                }
            }

            // We iterate through the rules and keep adding the rule load to the
            // cumulative total load  value.  If the total  load is greater than
            // chance  percentage, that rule  is selected.  For  e.g  if we have
            // rules R1 - load 30, and R2 load 50. If chance percentage is 40 in
            // the second iteration totalLoad becomes 80  > 40 and we select R2.
            // However if say the chance percentage was 90, then even  after all
            // iterations  totalLoad will  be 80 which is less than 90 and so no
            // rules will be selected
            if ($totalLoad >= $chancePercent)
            {
                if (empty($boostedTerminals) === false)
                {
                    $this->traceBoostedTerminals($boostedTerminals, $chancePercent, $verbose);
                }

                return $boostedTerminals;
            }
       }

       return null;
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
