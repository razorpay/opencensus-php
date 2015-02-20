<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

class EloquentEx extends \Razorpay\Spine\Entity
{
    public $incrementing = false;

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

    protected function throwException(array $e)
    {
        throw new Exception\DbQueryException($e);
    }

    public static function findOrFailPublic($id, $columns = array('*'))
    {
        if ( ! is_null($model = static::find($id, $columns))) return $model;

        $e = array(
                'model' => get_called_class(),
                'attributes' => $id,
                'operation' => 'find');

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID);
    }

    public function reload()
    {
        $instance = new static;

        $instance = $instance->newQuery()->find($this->{$this->primaryKey});

        $this->attributes = $instance->attributes;

        $this->original = $instance->original;

        return $this;
    }

    public function attributesToArray()
    {
        $attributes = parent::attributesToArray();

        foreach ($this->getDates() as $key)
        {
            if ( ! isset($attributes[$key])) continue;

            $attributes[$key] = (int) $attributes[$key];
        }

        return $attributes;
    }

    public function freshTimestamp()
    {
        return time();
    }
}
