<?php

namespace RZP\Models\Terminal;

use App;

use Illuminate\Database\Eloquent\Collection;
use RZP\Trace;
use RZP\Exception;
use RZP\Trace\TraceCode;

class Filter
{
    /**
     * This should be overridden in the child class with the respective filter properties
     * @var array
     */
    protected $properties;

    public function filter2($currentTerminals, array $input, $verbose = false)
    {
        foreach ($this->properties as $filterProperty)
        {
            $filterFunction = $this->getFilterFunctionForProperty($filterProperty);

            $applicableTerminals = [];

            // From all possible current terminals
            foreach ($currentTerminals as $terminal)
            {
                // If the terminal matches the filter property, add to applicable terminals list
                if ($this->$filterFunction($terminal, $input) === true)
                {
                    $applicableTerminals[] = $terminal;
                }
                // Else, do nothing.
            }

            $this->traceTerminals($applicableTerminals, 'Terminals after applying ' . $filterFunction . ' property', $verbose);
            $currentTerminals = $applicableTerminals;
        }

        return $currentTerminals;
    }

    /**
     * Takes input as collection of terminals. Gets the list of all properties applicable to the
     * respective filter class (child class). For each filter property and each terminal, it checks
     * whether the terminal can be used with the filter property given. If it cannot, for the next
     * iteration, the terminal is removed from the applicable list of terminals and the process is repeated.
     *
     * @param Collection $applicableTerminals The list of terminals after removing the not-applicable
     *                                        terminals from the full list of terminals.
     * @param array $input
     * @param bool $verbose                   For tracing
     * @return array                          List of terminals after removing the not-applicable terminals
     *                                        from the received collection of terminals
     */
    public function filter($applicableTerminals, array $input, $verbose = false)
    {
        foreach ($this->properties as $filterProperty)
        {
            $filterFunction = $this->getFilterFunctionForProperty($filterProperty);

            // From all possible current terminals
            foreach ($applicableTerminals as $key => $terminal)
            {
                // If the terminal does not match for the given filter property,
                // remove it from the applicable list of terminals.
                if ($this->$filterFunction($terminal, $input) !== true)
                {
                    $applicableTerminals->forget($key);
                }
            }

            $this->traceTerminals($applicableTerminals, 'Terminals after applying ' . $filterFunction . ' property', $verbose);
        }

        return $applicableTerminals;
    }

    protected function getFilterFunctionForProperty($filterProperty)
    {
        return camel_case($filterProperty) . 'Filter';
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
