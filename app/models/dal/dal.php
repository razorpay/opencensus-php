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
}