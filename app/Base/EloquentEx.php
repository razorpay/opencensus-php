<?php

namespace RZP\Base;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Entity as E;

class EloquentEx extends \Razorpay\Spine\Entity
{
    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * Parent relations which are specified here will be ignored while
     * checking existence of associated entities while saving current entity.
     *
     * @var array
     */
    protected $ignoredRelations = [];

    public function save(array $options = [])
    {
        //
        // Check that all associated parent entities of current entity, exist in
        // the database before saving. This excludes relations which are present in
        // the ignoredRelations array.
        //
        $nonExistentRelations = array_filter($this->relations, function ($model, $relation)
        {
            return ($this->assertRelationExistence($relation, $model) === false);
        }, ARRAY_FILTER_USE_BOTH);

        if (count($nonExistentRelations) > 0)
        {
            throw new Exception\RuntimeException('All parent entities must exist before save', [
                'entity'                 => $this->entity,
                'non_existent_relations' => array_keys($nonExistentRelations),
            ]);
        }

        return parent::save($options);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @return \RZP\Models\Base\BuilderEx|static
     */
    public function newEloquentBuilder($query)
    {
        $builder = new BuilderEx($query);

        $queryTimeout = $this->getQueryTimeout();

        if (empty($queryTimeout) === false)
        {
            $builder->setQueryTimeout($queryTimeout);
        }

        return $builder;
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

    protected function assertRelationExistence(string $relation, $model): bool
    {
        return (in_array($relation, $this->ignoredRelations, true) === true) ?
                true :
                (($model instanceof Model) ? $model->exists : true);
    }

    /**
     * Every entity which wants to implement delete should use either the
     * SoftDeletes or HardDeletes traits. Deleting by default is not allowed
     * here.
     *
     */
    protected function performDeleteOnModel()
    {
        throw new Exception\LogicException('Delete not supported, Use either HardDeletes or SoftDeletes trait', null, [
            'entity' => $this->entity
        ]);
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat(): string
    {
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param mixed $date
     * @return string
     */
    protected function serializeDate($date): string
    {
        $date = $this->asDateTime($date);

        return $date->format($this->getDateFormat());
    }
}
