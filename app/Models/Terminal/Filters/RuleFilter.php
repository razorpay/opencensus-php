<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Gateway\Rule;
use RZP\Models\Terminal;

class RuleFilter extends Terminal\Filter
{
    public function filter(array $terminals, array $input, $verbose = false)
    {
        $merchant = $input['merchant'];

        if ($merchant->isFeatureEnabled(Feature::RULE_FILTER) === false)
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
        $filteredTerminals = [];

        foreach ($terminals as $terminal)
        {
            foreach ($rules as $rule)
            {
                $match = $rule->matches($terminal);

                if (($rule->isSelectFilter() === true) and ($match === true))
                {
                    $filteredTerminals[] = $terminal;
                }
                else if (($rule->isRejectFilter() === true) and ($match === false))
                {
                    $filteredTerminals[] = $terminal;
                }
            }
        }

        $terminals = $filteredTerminals;
    }
}
