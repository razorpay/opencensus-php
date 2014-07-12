<?php

namespace Models\Base;

use EE\Error\ErrorCode;
use EE\Exception;

class EloquentEx extends \Eloquent
{
    public static function createOrFail(array $attributes)
    {
        if ( ! (NULL === $model = static::create($attributes))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => 'create');

        throw new Exception\DbQueryException($e);
    }

    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail(array $options = array())
    {
        $saved = parent::save($options);

        if ($saved === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'save');

        throw new Exception\DbQueryException($e);
    }

    public function pushOrFail()
    {
        $pushed = parent::push();

        if ($pushed === true)
            return;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $this->attributes,
                'operation' => 'push');

        throw new Exception\DbQueryException($e);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \Models\Base\BuilderEx|static
     */
    public function newEloquentBuilder($query)
    {
        return new BuilderEx($query);
    }
}
