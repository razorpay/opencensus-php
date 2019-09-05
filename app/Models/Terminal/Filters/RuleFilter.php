<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Base;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Models\Gateway\Rule;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Feature\Constants as Feature;

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
        try
        {
            // Temporarily setting verbosity to true for this filter
            $verbose = true;

            if ($this->rules->isEmpty() === true)
            {
                return $terminals;
            }

            $ruleGroups = $this->rules->groupBy(Rule\Entity::GROUP);

            $this->traceFilterRules($ruleGroups, $verbose);

            foreach ($ruleGroups as $group => $rules)
            {
                if ($this->shouldUseRuleGroup($group) === true)
                {
                    $this->filterTerminalsForGroup($terminals, $rules, $verbose);
                }
            }

            return $terminals;
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::TERMINAL_RULE_FILTER_EXCEPTION,
                [
                    'terminal_ids' => array_pluck($terminals, 'id'),
                ]);

            return $terminals;
        }
    }

    /**
     * Performs filtering of terminals within a group. Within a group all rules
     * have an OR operartion between them.
     * For example if two SELECT rules are present in a group it means
     * SELECT terminal A or SELECT terminal B
     *
     * @param array                 $terminals  Terminals to be filtered in group
     * @param  Base\PublicCollection $rules     Applicable rules for the group
     * @param  bool                  $verbose   Flag to turn on / off verbose logging
     */
    protected function filterTerminalsForGroup(array & $terminals, Base\PublicCollection $rules, bool $verbose)
    {
        $selectedTerminals = [];

        $rejectedTerminals = [];

        $group = $rules->first()->getGroup();

        $payment = $this->input['payment'];

        $gatewayTokens = $this->input['gateway_tokens'];

        foreach ($terminals as $terminal)
        {
            foreach ($rules as $rule)
            {
                $match = $rule->matches($terminal, $this->input['merchant'], $payment, $gatewayTokens);

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

        // If any select rules are present we only proceed with the set of
        // selected_terminals. If no select rules are present we take the diff
        // of all terminals and rejected terminals
        if ($isSelectRulePresent === false)
        {
            $filteredTerminals = array_diff($terminals, $rejectedTerminals);
        }

        // In certain cases, like 2 select rules in same group selecting the same terminal
        // we can have duplicate entries. Hence running a final unique check
        $terminals = $this->getUniqueTerminals($filteredTerminals);

        $data = [
            'selected' => $selectedTerminals,
            'rejected' => $rejectedTerminals,
            'final'    => $terminals,
        ];

        $this->traceTerminalsForGroup($data, $group, $verbose);
    }

    protected function isSelectRulePresent(Base\PublicCollection $rules): bool
    {
        return $rules->contains(function ($rule)
        {
            return ($rule->getAttribute(Rule\Entity::FILTER_TYPE) === Rule\Entity::SELECT);
        });
    }

    protected function getUniqueTerminals(array $terminals): array
    {
        $terminalIds = array_pluck($terminals, 'id');

        $uniqueTerminals = [];

        foreach ($terminals as $terminal)
        {
            $uniqueTerminalIds = array_pluck($uniqueTerminals, 'id');

            if (in_array($terminal->getId(), $uniqueTerminalIds, true) === false)
            {
                $uniqueTerminals[] = $terminal;
            }
        }

        return $uniqueTerminals;
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

    protected function traceTerminalsForGroup(array $data, string $group, bool $verbose)
    {
        if ($verbose === true)
        {
            $traceData = array_map(function (array $terminals)
            {
                return array_pluck($terminals, 'gateway', 'id');
            }, $data);

            $traceData['group'] = $group;

            $this->trace->info(TraceCode::TERMINAL_SELECTION_FOR_RULE_GROUP, $traceData);
        }
    }

    /**
     * Checks if a particular rule group should be used for filtering, based on feature
     * check or if the corresponding filter property is being skipped for all merchants
     *
     * @param  string $group Group name
     * @return bool          Whether to use the particular rule group for filtering
     */
    protected function shouldUseRuleGroup(string $group): bool
    {
        if ($this->input['merchant']->isFeatureEnabled(Feature::RULE_FILTER) === true)
        {
            return true;
        }

        $globallyApplicableRuleGroups = $this->options->getGloballyApplicableRuleGroups();

        $ruleGroupToFeature = $this->options->getRuleGroupMapToFeature();

        $result = (in_array($group, $globallyApplicableRuleGroups, true) === true);

        if (empty($ruleGroupToFeature[$group]) === false)
        {
            $feature = $ruleGroupToFeature[$group];

            $result = ($result & $this->input['merchant']->isFeatureEnabled($feature));
        }

        return $result;
    }
}
