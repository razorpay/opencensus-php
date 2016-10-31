<?php

namespace RZP\Base;

use RZP\Constants\Entity as E;
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

    public function getTable()
    {
        return E::getTableNameForEntity($this->entity);
    }

    protected function getAttributeWithTableName($col)
    {
        return $this->getTable() . '.' . $col;
    }

    public function scopeBetweenTime($query, $from, $to)
    {
        $createdAtColumn = $this->getAttributeWithTableName(Common::CREATED_AT);
        $query->whereBetween($createdAtColumn, [$from, $to]);
    }

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->getAttributeWithTableName(Common::MERCHANT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }

    public function scopeOrderByCreatedAt($query, $desc = true)
    {
        $desc = ($desc) ? 'desc' : 'asc';

        $query->orderBy(Common::CREATED_AT, $desc);
    }

    public static function createOrFail(array $attributes)
    {
        throw new Exception\RuntimeException('Use createOrFail via Repository');
    }
}
