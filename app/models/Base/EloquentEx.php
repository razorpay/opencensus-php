<?php

namespace Models\Base;

use EE\Exception;

class EloquentEx extends \Eloquent
{
    public static function createOrFail(array $attributes)
    {
        if ( ! (NULL === $model = static::create($attributes))) return $model;

        (new static)->throwException('create', $attributes);
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

        $this->throwException('save');
    }

    public function pushOrFail()
    {
        $pushed = parent::push();

        if ($pushed === true)
            return;

        $this->throwException('push');
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

    protected function throwException($operation, $attributes = null)
    {
        $e = $this->getExceptionDataArray($operation, $attributes);

        throw new Exception\DbQueryException($e);
    }

    protected function getExceptionDataArray($operation, $attributes = null)
    {
        if ($attributes === null)
            $attributes = $this->attributes;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $attributes,
                'operation' => $operation);

        return $e;
    }
}
