<?php

namespace RZP\Models\Base;

use RZP\Exception;
use RZP\Error\ErrorCode;

class EloquentEx extends \Razorpay\Spine\Entity
{
    public $incrementing = false;

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \RZP\Models\Base\BuilderEx|static
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
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $e);
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

    public static function getTableName()
    {
        return (new static)->getTable();
    }

    public static function getAttributeWithTableName($col)
    {
        return static::getTableName() . '.' . $col;
    }

    public function scopeBetweenTime($query, $from, $to)
    {
        $createdAtColumn = static::getAttributeWithTableName(Common::CREATED_AT);
        $query->whereBetween($createdAtColumn, [$from, $to]);
    }

    public function scopeMerchantId($query, $merchantId)
    {
        $table = $this->getTable();
        $merchantIdColumn = $table . '.' . Common::MERCHANT_ID;

        $query->where($merchantIdColumn, '=', $merchantId);
    }

    public function scopeOrderByCreatedAt($query, $desc = true)
    {
        $desc = ($desc) ? 'desc' : 'asc';

        $query->orderBy(Common::CREATED_AT, $desc);
    }
}
