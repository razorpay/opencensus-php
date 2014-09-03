<?php

namespace Models\DAL;

class DAL extends \Eloquent
{
    public static function createOrFail(array $attributes)
    {
        if ( ! (NULL === $model = static::create($attributes))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => 'create');

        throw new DbQueryException($e);
    }

    protected function getDateFormat()
    {
        return 'U';
    }

    protected function asDateTime($value)
    {
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
}