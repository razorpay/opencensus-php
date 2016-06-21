<?php

namespace Models\Terminal;

class Filter
{
    public function __construct()
    {

    }

    public function filter($terminals, $input)
    {
        $currentTerminals = $terminals;

        // For every property as part of a filter
        foreach (self::$properties as $filterProperty)
        {
            $filterName = camel_case($filterProperty).'Filter';

            // From all possible current terminals
            foreach ($currentTerminals as $terminal)
            {
                // If filter property matches, consider forward
                if ($this->$filterName($terminal, $input))
                {
                    $currentTerminals[] = $terminal;
                }
                // Else do nothing.
            }
        }
    }
}
