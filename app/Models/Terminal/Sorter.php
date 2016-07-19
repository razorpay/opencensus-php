<?php

namespace RZP\Models\Terminal;

use App;

use RZP\Trace;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
class Sorter
{
    public function sort($terminals, $input, $verbose = false)
    {
        // If only terminal left no need for sorter.
        if (count($terminals) === 1)
        {
            return $terminals;
        }

        $currentTerminals = $terminals;

        // For every property as part of a sorter
        foreach ($this->properties as $sorterProperty)
        {
            $sorterName = $this->getSorterNameForProperty($sorterProperty);

            $currentTerminals = $this->$sorterName($currentTerminals, $input);

            $this->traceTerminals($currentTerminals, 'Terminals after applying '.$sorterName.' property', $verbose);
        }

        return $currentTerminals;
    }

    protected function getSorterNameForProperty($sorterProperty)
    {
        return camel_case($sorterProperty).'Sorter';
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
