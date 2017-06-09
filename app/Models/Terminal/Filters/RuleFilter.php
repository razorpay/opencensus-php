<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Gateway\Rule;

class RuleFilter
{
    public function filter(array $terminals, array $input)
    {
        $featureCheck = true;

        if ($featureCheck === false)
        {
            return $terminals;
        }

        $applicableRules = (new Rule\Core)->fetchApplicableRulesForPayment($terminals, $input);

        $filterRules = $applicableRules->filter(function ($rule)
        {
            return $rule->isTypeFilter();
        });

        $ruleGroups = $filterRules->groupBy(Rule\Entity::GROUP);

        foreach ($ruleGroups as $group => $rules)
        {
            $selectedTerminalsForGroup = [];

            foreach ($terminals as $terminal)
            {
                foreach ($rules as $rule)
                {
                    if ($rule->isSelectFilter() === true)
                    {
                        if ($rule->matches($terminal) === true)
                        {
                            $selectedTerminalsForGroup[] = $terminal;
                        }
                    }
                    else if ($rule->isRejectFilter() === true)
                    {
                        if ($rule->matches($terminal) === false)
                        {
                            $selectedTerminalsForGroup[] = $terminal;
                        }
                    }
                }
            }

            $terminals = $selectedTerminalsForGroup;
        }

        s(array_pluck($terminals, 'gateway', 'id'));

        return $terminals;
    }
}
