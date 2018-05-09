<?php

namespace RZP\Base;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;
use RZP\Base\Database\QueryBuilder;

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

    /**
     * Overriden to return the custom database query builder instance
     *
     * @return QueryBuilder;
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
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

        foreach ($this->dates as $key)
        {
            if ( ! isset($attributes[$key])) continue;

            $attributes[$key] = (int) $attributes[$key];
        }

        return $attributes;
    }

    public function freshTimestamp()
    {
        return Carbon::now()->getTimestamp();
    }

    public function getTable()
    {
        return E::getTableNameForEntity($this->entity);
    }

    protected function dbColumn($col)
    {
        return $this->getTable() . '.' . $col;
    }

    public function scopeBetweenTime($query, $from, $to)
    {
        $createdAtColumn = $this->dbColumn(Common::CREATED_AT);
        $query->whereBetween($createdAtColumn, [$from, $to]);
    }

    public function scopeOrgId($query, $orgId)
    {
        $orgIdColumn = $this->dbColumn('org_id');

        $query->where($orgIdColumn, '=', $orgId);
    }

    public function scopeMerchantId($query, $merchantId)
    {
        $merchantIdColumn = $this->dbColumn(Common::MERCHANT_ID);

        $query->where($merchantIdColumn, '=', $merchantId);
    }

    public function scopeOrderByCreatedAt($query, $desc = true)
    {
        $desc = ($desc) ? 'desc' : 'asc';

        $query->orderBy(Common::CREATED_AT, $desc);
    }

    public function scopeCreatedAtLessThan($query, $createdAt)
    {
        $createdAtColumn = $this->dbColumn(Common::CREATED_AT);
        return $query->where($createdAtColumn, '<', $createdAt);
    }

    public function scopeCreatedAtGreaterThan($query, $createdAt)
    {
        $createdAtColumn = $this->dbColumn(Common::CREATED_AT);
        return $query->where($createdAtColumn, '>', $createdAt);
    }

    public static function createOrFail(array $attributes)
    {
        throw new Exception\RuntimeException('Use createOrFail via Repository');
    }

    protected function isAttributeNotNull($attr)
    {
        return (is_null($this->getAttribute($attr)) === false);
    }

    public function hasRelation($relation)
    {
        return (empty($this->relations[$relation]) === false);
    }
}
