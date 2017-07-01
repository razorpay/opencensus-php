<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Base;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Gateway\Rule;
use RZP\Models\Terminal;

class RuleFilter extends Terminal\Filter
{
    public function filter(array $terminals, $verbose = false)
    {
        $merchant = $this->input['merchant'];

        if (($this->rules->isEmpty() === true) or
            ($merchant->isFeatureEnabled(Feature::RULE_FILTER) === false))
        {
            return $terminals;
        }

        $ruleGroups = $this->rules->groupBy(Rule\Entity::GROUP);

        foreach ($ruleGroups as $group => $rules)
        {
            $this->filterTerminalsForGroup($terminals, $rules);
        }

        return $terminals;
    }

    protected function filterTerminalsForGroup(array & $terminals, Base\PublicCollection $rules)
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

        $isSelectRulePresent = $this->isSelectRulePresent($rules);

        $filteredTerminals = $selectedTerminals;

        if ($isSelectRulePresent === false)
        {
            $filteredTerminals = array_diff($terminals, $rejectedTerminals);
        }

        $terminals = array_values(array_unique($filteredTerminals));
    }

    protected function isSelectRulePresent(Base\PublicCollection $rules)
    {
        return $rules->contains(function ($rule)
        {
            return ($rule->getAttribute(Rule\Entity::FILTER_TYPE) === Rule\Entity::SELECT);
        });
    }
}
