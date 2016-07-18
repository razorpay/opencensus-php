<?php

namespace RZP\Models\Terminal;

use App;

use RZP\Trace;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
class Filter
{
    public function filter($terminals, $input, $verbose = false)
    {
        $currentTerminals = $terminals;

        // For every property as part of a filter
        foreach ($this->properties as $filterProperty)
        {
            $filterName = $this->getFilterNameForProperty($filterProperty);

            $testTerminals = [];
            // From all possible current terminals
            foreach ($currentTerminals as $terminal)
            {
                // If filter property matches, consider forward
                if ($this->$filterName($terminal, $input))
                {
                    $testTerminals[] = $terminal;
                }
                // Else do nothing.
            }
            $this->traceTerminals($testTerminals, 'Terminals after applying '.$filterName.' property', $verbose);
            $currentTerminals = $testTerminals;
        }

        return $currentTerminals;
    }

    protected function getFilterNameForProperty($filterProperty)
    {
        return camel_case($filterProperty).'Filter';
    }

    protected function traceTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose) and
            ($terminals))
        {
            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $traceData = ['count' => count($terminals), 'terminals' => $terminalIds, 'msg' => $msg];

            $trace = \App::getFacadeRoot()['trace'];
            $trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }
}
