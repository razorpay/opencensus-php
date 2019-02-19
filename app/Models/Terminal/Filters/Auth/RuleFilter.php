<?php

namespace RZP\Models\Terminal\Filters\Auth;

use RZP\Models\Base;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;

class RuleFilter extends Terminal\Filter
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

    protected function filterTerminalsForGroup(array & $terminals, Base\PublicCollection $rules, bool $verbose, string $group)
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

        $terminals = $selectedTerminals;

        $data = [
            'selected' => $selectedTerminals,
            'rejected' => $rejectedTerminals,
            'final'    => $terminals,
        ];

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
