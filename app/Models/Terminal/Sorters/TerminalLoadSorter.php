<?php

namespace RZP\Models\Terminal\Sorters;

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
     * Rules for selecting a terminal with some probability
     * Attributes must be one of the terminal entity attribute which is matched
     * with terminal property
     */
    protected static $rules = [
        [
            'load'          => 5,
            'attributes'    => [
                Terminal\Entity::GATEWAY           => Gateway::FIRST_DATA
            ]
        ],
        [
            'load'          => 45,
            'attributes'    => [
                Terminal\Entity::GATEWAY            => Gateway::AXIS_MIGS
            ]
        ],
        [
            'load'      => 0,
            'attributes'    => [
                Terminal\Entity::GATEWAY            => Gateway::CYBERSOURCE,
                Terminal\Entity::GATEWAY_ACQUIRER   => Gateway::ACQUIRER_HDFC,
            ]
        ],
        [
            'load'      => 5,
            'attributes'    => [
                Terminal\Entity::GATEWAY            => Gateway::CYBERSOURCE,
                Terminal\Entity::GATEWAY_ACQUIRER   => Gateway::ACQUIRER_AXIS,
            ]
        ],
    ];

    public function getRules()
    {
        return self::$rules;
    }

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
        // $sortedTerminals = $terminals;

        $merchantId = $input['merchant']->getId();

        $applicableRules = (new LoadRule\Core)->fetchApplicableRules($terminals, $input);

        // If no rules are present for load sorting we return the terminals list as is
        if ($applicableRules->isEmpty() === true)
        {
            return $terminals;
        }

        //
        // We match terminals to the eligible rules based on terminal criteria
        // and generate a map with the structure
        // [
        //      <terminal_id> => <load_value>
        // ]
        //
        $terminalToLoadMap = (new LoadRule\Core)->matchTerminalToRule($terminals, $applicableRules);

        if (is_null($options) === false)
        {
            $chancePercent = $options->getChance();

            $boostedTerminalIds = $this->getBoostedTerminalIds($terminals, $chancePercent);

            if (is_null($boostedTerminalIds) === false)
            {
                $boostedTerminals = [];

                $nonBoostedTerminals = [];

                // As the terminals are from the priority list
                // append to the terminal
                foreach ($terminals as $terminal)
                {
                    if (in_array($terminal->getId(), $boostedTerminalIds, true))
                    {
                        $boostedTerminals[] = $terminal;
                    }
                    else
                    {
                        $nonBoostedTerminals[] = $terminal;
                    }
                }

                $sortedTerminals = array_merge($boostedTerminals, $nonBoostedTerminals);
            }
        }

        return $terminals;
    }

    protected function getMerchantSpecificRules(Base\PublicCollection $applicableRules, string $merchantId)
    {
        $merchantSpecificRules = $applicableRules->filter(function ($rule) use ($merchantId)
        {
            return $rule->getMerchantId() === $merchantId;
        });

        return $merchantSpecificRules;
    }

    protected function getBoostedTerminalIds(array $terminals, $chancePercent)
    {
        // Not all rules will apply, a terminal may already have
        // been rejected in the previous sorting/filtering steps.
        $applicableRules = $this->getApplicableRules($terminals);

        $cumulativeProbabity = 0;

        foreach ($applicableRules as $rule)
        {
            $cumulativeProbabity += $rule['load'];

            $valid = $this->validateRules($cumulativeProbabity, $applicableRules);

            if ($valid === false)
            {
                // Rules are invalid. Don't boost any terminal.
                return null;
            }

            // Checking > 100-p, rather than simply <p
            // because in test cases we're always setting
            // p to zero, to avoid unexpected behaviour.
            if ($chancePercent > (100 - $cumulativeProbabity))
            {
                return $rule['ids'];
            }
        }

        return null;
    }

    protected function getApplicableRules($terminals)
    {
        $allRules = $this->getRules();

        $applicableRules = [];

        foreach ($allRules as $rule)
        {
            // If the rule has any matching terminal then only merge
            // it to the applicableRules array
            $merge = false;

            // Rules only apply to terminals that have made it
            // this far in the selection process
            foreach ($terminals as $terminal)
            {
                if ($this->validateAttributes($rule['attributes'], $terminal))
                {
                    $rule['ids'][] = $terminal->getId();
                    $merge = true;
                }
            }

            if ($merge === true)
            {
                $applicableRules[] = $rule;
            }
        }

        return $applicableRules;
    }

    protected function validateAttributes($attributes, $terminal)
    {
        // Get all terminal attributes
        $termAttributes = $terminal->getAttributes();

        // Gets diff of the two. If there is any diff,
        // then attributes are not perfectly matching.
        return (count(array_diff_assoc($attributes, $termAttributes)) === 0);
    }

    protected function validateRules($cumulativeProbability, $applicableRules)
    {
        // Cumulative probability for all applicable rules
        // can't possibly be above 100. In this case, don't
        // boost any terminal.
        if ($cumulativeProbability > 100)
        {
            $this->trace->error(
                TraceCode::TERMINAL_BOOST_INVALID,
                [
                    'cumulative_probabity' => $cumulativeProbability,
                    'applicable_rules'     => $applicableRules,
                ]
            );

            return false;
        }

        return true;
    }
}
