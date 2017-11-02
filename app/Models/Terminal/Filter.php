<?php

namespace RZP\Models\Terminal;

use Trace;
use RZP\Exception;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Filter extends Base\Core
{
    /**
     * This should be overridden in the child class with the respective filter properties
     * @var array
     */
    protected $properties;

    protected $input;

    protected $rules;

    protected $options;

    public function __construct(array $input, Options $options, Base\PublicCollection $rules)
    {
        parent::__construct();

        $this->input = $input;

        $this->options = $options;

        $this->rules = $rules;
    }

    /**
     * Takes input as collection of terminals. Gets the list of all properties applicable to the
     * respective filter class (child class). For each filter property and each terminal, it checks
     * whether the terminal can be used with the filter property given. If it cannot, for the next
     * iteration, the terminal is removed from the applicable list of terminals and the process is repeated.
     *
     * @param array $applicableTerminals       The list of terminals after removing the not-applicable
     *                                         terminals from the full list of terminals.
     * @param bool  $verbose                   For tracing
     * @return array                           List of terminals after removing the not-applicable terminals
     *                                         from the received collection of terminals
     */
    public function filter(array $applicableTerminals, $verbose = false)
    {
        foreach ($this->properties as $filterProperty)
        {
            if ($this->shouldSkipFilter($filterProperty) === true)
            {
                continue;
            }

            $filterFunction = $this->getFilterFunctionForProperty($filterProperty);

            // From all possible current terminals
            foreach ($applicableTerminals as $key => $terminal)
            {
                // If the terminal does not match for the given filter property,
                // remove it from the applicable list of terminals.
                if ($this->$filterFunction($terminal, $applicableTerminals) !== true)
                {
                    unset($applicableTerminals[$key]);
                }
            }

            $this->traceTerminals(
                $applicableTerminals,
                'Terminals after applying ' . $filterFunction . ' property',
                $verbose,
                $this->input['merchant']->getId());
        }

        // array_values is being used to reindex the array after un-setting.
        return array_values($applicableTerminals);
    }

    protected function getFilterFunctionForProperty($filterProperty)
    {
        return camel_case($filterProperty) . 'Filter';
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

            $trace = Trace::getFacadeRoot();

            $trace->info(TraceCode::TERMINAL_SELECTION, $traceData);
        }
    }

    protected function shouldSkipFilter(string $property): bool
    {
        $merchant = $this->input['merchant'];

        $featureSkippedFilters = $this->options->getFeatureSkippedFilters();

        $isFilterSkipped = in_array($property, $featureSkippedFilters, true);

        $featureEnabled = $merchant->isFeatureEnabled(Feature::RULE_FILTER);

        return (($isFilterSkipped === true) and ($featureEnabled === true));
    }
}
