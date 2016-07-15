<?php

namespace RZP\Models\Terminal;

class Sorter
{
    public function sort($terminals, $input)
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
        }

        return $currentTerminals;
    }

    protected function getSorterNameForProperty($sorterProperty)
    {
        return camel_case($sorterProperty).'Sorter';
    }
}
