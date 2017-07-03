<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Base;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Gateway\Rule;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;

class RuleFilter extends Terminal\Filter
{
    /**
     * Filters terminals across rule groups. Each rule group represents a funnel
     * in the filter pipeline. Terminals filtered by one group are passed onto the next
     * group and thus the final set of terminals is obtained
     *
     * @param  array   $terminals List of terminals to be filtered
     * @param  boolean $verbose   Whether verbose logging should be enabled
     * @return array              Final list of filtered terminals
     */
    public function filter(array $terminals, $verbose = false)
    {
        // Temporarily setting verbosity to true for this filter
        $verbose = true;

        $merchant = $this->input['merchant'];

        if (($this->rules->isEmpty() === true) or
            ($merchant->isFeatureEnabled(Feature::RULE_FILTER) === false))
        {
            return $terminals;
        }

        $ruleGroups = $this->rules->groupBy(Rule\Entity::GROUP);

        $this->traceFilterRules($ruleGroups, $verbose);

        foreach ($ruleGroups as $group => $rules)
        {
            $this->filterTerminalsForGroup($terminals, $rules, $verbose);
        }

        return $terminals;
    }

    /**
     * Performs filtering of terminals within a group. Within a group all rules
     * have an OR operartion between them.
     * For example if two SELECT rules are present in a group it means
     * SELECT terminal A or SELECT terminal B
     *
     * @param array                 $terminals  Terminals to be filtewred in group
     * @param  Base\PublicCollection $rules     Applicable rules for the group
     * @param  bool                  $verbose   Flag to turn on / off verbose logging
     */
    protected function filterTerminalsForGroup(array & $terminals, Base\PublicCollection $rules, bool $verbose)
    {
        $selectedTerminals = [];

        $rejectedTerminals = [];

        foreach ($terminals as $terminal)
        {
            foreach ($rules as $rule)
            {
                $match = $rule->matches($terminal);

                if ($match === true)
                {
                    if ($rule->shouldSelectTerminal() === true)
                    {
                        $selectedTerminals[] = $terminal;
                    }
                    else if ($rule->shouldRejectTerminal() === true)
                    {
                        $rejectedTerminals[] = $terminal;
                    }
                }
            }
        }

        $this->traceTerminals($selectedTerminals, 'selected_terminals', $verbose);

        $this->traceTerminals($rejectedTerminals, 'rejected_terminals', $verbose);

        $isSelectRulePresent = $this->isSelectRulePresent($rules);

        $filteredTerminals = $selectedTerminals;

        if ($isSelectRulePresent === false)
        {
            $filteredTerminals = array_diff($terminals, $rejectedTerminals);
        }

        // In certain cases, like 2 select rules in same group seelecting the same terminal
        // we can have duplicate entries. Hence running a final unique check
        $terminals = array_values(array_unique($filteredTerminals));

        $this->traceTerminals($terminals, 'filtered_terminals', $verbose);

    }

    protected function isSelectRulePresent(Base\PublicCollection $rules)
    {
        return $rules->contains(function ($rule)
        {
            return ($rule->getAttribute(Rule\Entity::FILTER_TYPE) === Rule\Entity::SELECT);
        });
    }

    protected function traceFilterRules(Base\PublicCollection $ruleGroups, bool $verbose)
    {
        if ($verbose === true)
        {
            $traceData = [];

            foreach ($ruleGroups as $group => $rules)
            {
                $traceData[$group] = [];

                foreach ($rules as $rule)
                {
                    $traceData[$group][] = [
                        'id'          => $rule->getId(),
                        'gateway'     => $rule->getGateway(),
                        'filter_type' => $rule->getFilterType()
                    ];
                }
            }

            $this->trace->info(TraceCode::GATEWAY_FILTER_RULES, $traceData);
        }
    }
}
