<?php

namespace RZP\Models\Terminal;

use App;

use RZP\Trace;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Sorter
{
    /**
     * This should be overridden in the child class with the respective sorter properties
     * @var array
     */
    protected $properties;

    public function sort($terminals, $input, $verbose = false)
    {
        // No need to sort if there's only one terminal
        if (count($terminals) === 1)
        {
            return $terminals;
        }

        $currentTerminals = $terminals;

        // For every property as part of a sorter
        foreach ($this->properties as $sorterProperty)
        {
            $sorterFunction = $this->getSorterNameForProperty($sorterProperty);

            $currentTerminals = $this->$sorterFunction($currentTerminals, $input);

            $this->traceTerminals($currentTerminals, 'Terminals after applying ' . $sorterFunction . ' property', $verbose);
        }

        return $currentTerminals;
    }

    protected function getSorterNameForProperty($sorterProperty)
    {
        return camel_case($sorterProperty) . 'Sorter';
    }

    protected function traceTerminals($terminals, $msg, $verbose = false)
    {
        if (($verbose) and ($terminals))
        {
            $terminalIds = [];

            foreach ($terminals as $terminal)
            {
                $terminalIds[] = $terminal->getId();
            }

            $traceData = ['count' => count($terminals), 'terminals' => $terminalIds, 'msg' => $msg];

            $trace = App::getFacadeRoot()['trace'];

            $trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }
}
