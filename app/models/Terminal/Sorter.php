<?php

namespace Models\Terminal;

class Sorter
{
    public function sort($terminals, $input)
    {
        if (count($terminals) === 1)
        {
            return $terminals;
        }

        $currentTerminals = $terminals;

        // For every property as part of a sorter
        foreach ($this->properties as $sorterProperty)
        {
            $sorterName = camel_case($sorterProperty).'Sorter';

            $currentTerminals = $this->$sorterName($currentTerminals, $input);
        }

        return $currentTerminals;
    }
}
