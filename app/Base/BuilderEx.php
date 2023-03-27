<?php

namespace RZP\Base;

use Illuminate\Database\Query\JoinClause;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\SyncEventManager;

class BuilderEx extends \Razorpay\Spine\BuilderEx
{
    public function findOrFailPublic($id, $columns = array('*'))
    {
        $model = $this->find($id, $columns);

        if (is_null($model) === false)
        {
            return $model;
        }

        $data = [
                'model' => get_class($this->model),
                'attributes' => $id,
                'operation' => 'find'
            ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws Exception\BadRequestException
     */
    public function firstOrFailPublic($columns = array('*'))
    {
        if ( ! is_null($model = $this->first($columns))) return $model;

        $data = array(
                'model' => get_class($this->model),
                'attributes' => $columns,
                'operation' => 'find');

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND, null, $data);
    }

    /**
     * Queries for many entity by ids.
     * If any single of the given ids are not found, fails with bad request.
     *
     * @param array $ids
     * @param array $columns
     *
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @throws Exception\BadRequestException
     */
    public function findManyOrFailPublic(array $ids, array $columns = ['*'])
    {
        $models = $this->findMany($ids, $columns);

        //
        // If all of the requested ids are found, return the collection.
        //
        if ($models->count() === count($ids))
        {
            return $models;
        }

        //
        // All ids not found so trace the diff with attributes
        // holding all the not found ids.
        //
        $foundIds = $models->pluck('id')->toArray();

        $notFoundIds = array_diff($ids, $foundIds);

        $extra = [
            'model' => get_class($this->model),
            'attributes' => $notFoundIds,
            'operation' => 'findMany',
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_IDS, null, $extra);
    }

    public function hasJoin(string $table): bool
    {
        $joins = $this->getQuery()->joins ?? [];

        /** @var JoinClause $join */
        foreach ($joins as $join)
        {
            if ($join->table === $table)
            {
                return true;
            }
        }

        return false;
    }

    public function get($columns = ['*'])
    {
        $collection = parent::get($columns);

        $collection = $this->setMissingEagerLoad($collection);

        try
        {
            $entityName = $this->getModel()->getEntityName();
            if (in_array($entityName, E::ACS_SYNCED_ENTITIES) === true)
            {
                $logData = app(SyncEventManager::SINGLETON_NAME)->getLogData($this->getModel(), [], $collection);

                app(SyncEventManager::SINGLETON_NAME)->logEntityFetch($logData);
            }
        }
        catch (\Exception $ex)
        {
            app('trace')->traceException($ex, Trace::ERROR, TraceCode::ACS_ENTITY_FETCH_EXCEPTION, []);
        }

        return $collection;
    }

    // Note : This function at the moment is solving only entity loading without considering $constraints
    // Ref : https://laravel.com/docs/9.x/eloquent-relationships
    private function setMissingEagerLoad($models)
    {
        foreach ($this->eagerLoad as $name => $constraints)
        {
            // For nested eager loads we'll skip loading them here, and they will be set as an
            // eager load on the query to retrieve the relation so that they will be eager
            // loaded on that query, because that is where they get hydrated as models.
            if (in_array($name, Entity::getCustomEagerLoadRelations(), true) === true)
            {
                $models = $this->customEagerLoad($name, $models);
            }
        }

        return $models;
    }

    // Currently, used for custom eager loading relations of archived entities
    private function customEagerLoad($name, $models)
    {
        // use config key for payment
        if ($name === Entity::PAYMENT)
        {
            $customPaymentEagerLoad = (bool) ConfigKey::get(ConfigKey::PAYMENT_ARCHIVAL_EAGER_LOAD, false);

            if ($customPaymentEagerLoad === false)
            {
                return $models;
            }
        }

        foreach ($models as $model)
        {
            if ($model->hasRelation($name) === false)
            {
                // Exceptions in this flow can be silent.
                try
                {
                    $relationId = $model->getAttribute(Entity::getCustomEagerLoadEntityKey($name));

                    if (empty($relationId) === false)
                    {
                        $repoClass = Entity::getEntityRepository(Entity::getCustomEagerLoadRelationEntity($name));

                        $entity = (new $repoClass)->findOrFail($relationId);

                        if (empty($entity) === false)
                        {
                            $model->setRelation($name, $entity);
                        }
                    }
                }
                catch (\Throwable $exception) {}
            }
        }
        return $models;
    }
}
