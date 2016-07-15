<?php

namespace RZP\Models\Terminal;

class Filter
{
    public function filter($terminals, $input)
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

            $currentTerminals = $testTerminals;
        }

        return $currentTerminals;
    }

    protected function getFilterNameForProperty($filterProperty)
    {
        return camel_case($filterProperty).'Filter';
    }
}
