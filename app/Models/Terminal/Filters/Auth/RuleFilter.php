<?php

namespace RZP\Models\Terminal\Filters\Auth;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use RZP\Models\Terminal\Filters\RuleFilter as BaseRuleFilter;

class RuleFilter extends BaseRuleFilter
{
    public function filter(array $terminals, $verbose = false)
    {
        $verbose = true;

        if ($this->rules->isEmpty() === true)
        {
            return $terminals;
        }

        $ruleGroups = $this->rules->groupBy(Rule\Entity::GROUP);

        $this->traceFilterRules($ruleGroups, $verbose);

        foreach ($ruleGroups as $group => $rules)
        {
            $this->filterTerminalsForGroup($terminals, $rules, $verbose, $group);
        }

        return $terminals;
    }

    protected function filterTerminalsForGroup(array & $terminals, Base\PublicCollection $rules, bool $verbose, string $group = '')
    {
        $selectedTerminals = [];

        $rejectedTerminals = [];

        $payment = $this->input['payment'];

        foreach ($terminals as $terminal)
        {
            foreach ($rules as $rule)
            {
                $match = $rule->matchesAuthTerminal($terminal, $payment);

                if ($match === true)
                {
                    if ($rule->shouldSelectAuth() === true)
                    {
                        $selectedTerminals[] = $terminal;
                    }
                    else if ($rule->shouldRejectAuth() === true)
                    {
                        $rejectedTerminals[] = $terminal;
                    }
                }
            }
        }

        $isSelectRulePresent = $this->isSelectRulePresent($rules);

        $filteredTerminals = $selectedTerminals;

        // If any select rules are present we only proceed with the set of
        // selected_terminals. If no select rules are present we take the diff
        // of all terminals and rejected terminals
        if ($isSelectRulePresent === false)
        {
            $filteredTerminals = array_udiff($terminals, $rejectedTerminals, function ($a, $b) {
                return strcmp(implode('', $a), implode('', $b));
            });

            // To fix the array keys after filtering
            $filteredTerminals = array_values($filteredTerminals);
        }

        $data = [
            'selected' => $selectedTerminals,
            'rejected' => $rejectedTerminals,
            'final'    => $filteredTerminals,
        ];

        $terminals = $filteredTerminals;

        $this->traceTerminalsForGroup($data, $group, $verbose);
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
                        'filter_type' => $rule->getFilterType(),
                        'auth_type'   => $rule->getAuthType(),
                        'authentication_gateway' => $rule->getAuthenticationGateway(),
                    ];
                }
            }

            $this->trace->info(TraceCode::AUTH_SELECTION_GATEWAY_RULES, $traceData);
        }
    }

    protected function traceTerminalsForGroup(array $data, string $group, bool $verbose)
    {
        if ($verbose === true)
        {
            $traceData = array_map(function (array $terminals)
            {
                return array_pluck($terminals, 'gateway', 'id');
            }, $data);

            $traceData['group'] = $group;

            $this->trace->info(TraceCode::AUTH_TERMINAL_SELECTION_FOR_RULE_GROUP, $traceData);
        }
    }
}
