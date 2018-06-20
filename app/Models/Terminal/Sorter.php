<?php

namespace RZP\Models\Terminal;

use App;
use Trace;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Sorter extends Base\Core
{
    /**
     * This should be overridden in the child class with the respective sorter properties
     * @var array
     */
    protected $properties;

    protected $input;

    protected $options;

    protected $rules;

    public function __construct(array $input, Options $options, array $rules = [])
    {
        parent::__construct();

        $this->input = $input;

        $this->rules = $rules;

        $this->options = $options;
    }

    public function sort($terminals, $verbose = false)
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

            $currentTerminals = $this->$sorterFunction($currentTerminals, $verbose);

            $this->traceTerminals(
                $currentTerminals,
                'Terminals after applying ' . $sorterFunction . ' property',
                $verbose,
                $this->input['merchant']->getId());
        }

        return $currentTerminals;
    }

    protected function getSorterNameForProperty($sorterProperty)
    {
        return camel_case($sorterProperty) . 'Sorter';
    }

    protected function traceTerminals($terminals, $msg, $verbose = false, $merchantId = null)
    {
        if ($merchantId === '4izmfM9TFCAgFN')
        {
            $verbose = true;
        }

        if (($verbose === true) and (empty($terminals) === false))
        {
            $terminalData = array_pluck($terminals, 'gateway', 'id');

            $traceData = ['count' => count($terminals), 'terminals' => $terminalData, 'msg' => $msg];

            $this->trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }
}
