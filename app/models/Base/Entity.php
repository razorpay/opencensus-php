<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class Entity extends EloquentEx
{
    protected function asDateTime($value)
    {
        //
        // If this value is an integer, we will assume
        // it is a UNIX timestamp's value and return as it is.
        // Otherwise we will call the parent function to handle it.
        //
        if (is_numeric($value))
        {
            return $value;
        }
        else
        {
            return parent::asDateTime($value);
        }
    }

    /**
     * It takes the input array used to create the entity
     * and replaces blanks '' with null
     *
     * @param  array    $input Takes input array by ref
     */
    protected function modifyInputRemoveBlanks(& $input)
    {
        foreach ($input as $key => $value)
        {
            if ($input[$key] === '')
            {
                $input[$key] = null;
            }
        }
    }

    /**
     * Get the default foreign key name for the model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        $class = get_class($this);
        $segments = explode('\\',$class);

        if (end($segments) === 'Entity')
            return snake_case(prev($segments)).'_id';
        else
            return parent::getForeignKey();
    }
}
