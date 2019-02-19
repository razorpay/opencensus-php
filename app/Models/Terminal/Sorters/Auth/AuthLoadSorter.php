<?php
namespace RZP\Models\Terminal\Sorters\Auth;

use RZP\Models\Base;
use RZP\Models\Gateway\Rule;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class AuthLoadSorter extends Terminal\Sorter
{
    protected $properties = [
        'auth_gateway',
    ];

    public function authGatewaySorter($terminals)
    {
        if (empty($this->rules) === true)
        {
            return $terminals;
        }

        $verbose = true;

        $this->traceSorterRules($verbose);

        $chancePercent = $this->options->getChance();

        $boostedTerminals = [];

        foreach ($this->rules as $score => $rules)
        {
            $boostedTerminals = $this->getBoostedTerminals(
                                        $terminals,
                                        $chancePercent,
                                        $rules,
                                        $verbose);

            if (empty($boostedTerminals) === false)
            {
                break;
            }
        }

        if (empty($boostedTerminals) === true)
        {
            return $terminals;
        }

        return $boostedTerminals;
    }

    protected function getBoostedTerminals(
                            array $terminals,
                            int $chancePercent,
                            Base\PublicCollection $rules,
                            bool $verbose = false)
    {
       $totalLoad = 0;

       $payment = $this->input['payment'];

       foreach ($rules as $rule)
       {
            $totalLoad += $rule->getLoad();

            if ($totalLoad < $chancePercent)
            {
                continue;
            }

            $boostedTerminals = [];

            foreach ($terminals as $terminal)
            {
                if ($rule->matchesAuthTerminal($terminal, $payment) === true)
                {
                    $boostedTerminals[] = $terminal;
                }
            }

            if (empty($boostedTerminals) === false)
            {
                $this->traceBoostedTerminals($boostedTerminals, $chancePercent, $verbose);
            }

            return $boostedTerminals;
       }

       return [];
    }

    protected function traceBoostedTerminals(array $terminals, int $chancePercent, bool $verbose = false)
    {
        if ($verbose === true)
        {
            $traceData = [];

            $traceData['chance_percent'] = $chancePercent;

            $terminalAuthTypes = array_pluck($terminals, 'auth_type');

            $traceData['boosted_terminals'] = $terminalAuthTypes;

            $this->trace->info(TraceCode::AUTH_LOAD_SORTING_BOOSTED_TERMINALS, $traceData);
        }
    }

    protected function traceSorterRules(bool $verbose = false)
    {
        if ($verbose === true)
        {
            $ruleIds = [];
            $traceData = [];
            foreach ($this->rules as $score => $rules)
            {
                foreach ($rules as $rule)
                {
                    $traceData[$score][] =[
                        'id'          => $rule->getId(),
                        'gateway'     => $rule->getGateway(),
                        'filter_type' => $rule->getFilterType(),
                        'auth_type'   => $rule->getAuthType(),
                        'load'        => $rule->getLoad(),
                        'authentication_gateway' => $rule->getAuthenticationGateway(),
                    ];
                }
            }

            $traceData['chance_percent'] = $this->options->getChance();

            $this->trace->info(
                TraceCode::AUTH_SELECTION_GATEWAY_RULES,
                $traceData
            );
        }
    }
}
