<?php

namespace RZP\Base;

use Closure;
use Illuminate\Database\Query\JoinClause;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Constants\Entity as E;
use RZP\Models\Admin\ConfigKey;
use Razorpay\Trace\Logger as Trace;
use RZP\Modules\Acs\SyncEventManager;
use RZP\Models\Offer\Core as OfferCore;

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

    protected function eagerLoadRelation(array $models, $name, Closure $constraints)
    {
        // Define the valid relation names
        $migratedEagerLoadRelations = ['merchant', 'merchantDetail', 'maker', 'emails', 'org', 'features', 'primaryBalance', 'offers'];

        // Call parent method for relations not in the valid list
        if (!in_array($name, $migratedEagerLoadRelations, true)) {
            return parent::eagerLoadRelation($models, $name, $constraints);
        }

        if ($name === "offers")
        {
            return $this->eagerLoadRelationsForOffer($models, $name, $constraints);
        }

        try {
            if ((new AsvRouter())->shouldRouteFilterToAsv("baseEagerLoadRelation")) {
                return $this->loadRelation($models, $name, $constraints);
            }
        } catch (\Exception $ex) {
            app('trace')->traceException($ex, Trace::ERROR, TraceCode::ASV_EAGER_LOAD_EXCEPTION, []);
        }

        return parent::eagerLoadRelation($models, $name, $constraints);
    }

    protected function eagerLoadRelationsForOffer($models, $name, $constraints)
    {
        try
        {
            if ((new OfferCore())->shouldEagerLoadOffersFromOE() === true)
            {
                return $this->loadRelation($models, $name, $constraints);
            }
        }
        catch (\Throwable $ex)
        {
            app('trace')->traceException($ex, Trace::ERROR, TraceCode::OFFERS_EAGER_LOAD_EXCEPTION, [
                'route_name' => app('api.route')->getCurrentRouteName(),
            ]);
        }

        return parent::eagerLoadRelation($models, $name, $constraints);
    }

    protected function loadRelation(array $models, $name, $constraints)
    {
        $result = [];
        foreach ($models as $model) {
            $method = 'get' . ucfirst($name) . 'Attribute'; // Dynamically create the method name

            if (method_exists($model, $method)) {
                // Access the attribute (triggers the method via magic)
                $loadedModel = $model->$name;
                if ($loadedModel != null) {
                    $nestedRelations = $this->relationsNestedUnder($name);
                    foreach ($nestedRelations as $relationName => $relationConstraints) {
                        // Relation nesting beyond 2 levels is not supported.
                        if (!str_contains($relationName, '.')) {
                            $this->eagerLoadRelation([$loadedModel], $relationName, $relationConstraints);
                        } else {
                            app('trace')->info(TraceCode::ASV_EAGER_LOAD_IMPLEMENTATION, [
                                "name" => $name,
                                "method" => $method,
                                "reason" => "Relation nesting beyond 2 levels"
                            ]);
                            return parent::eagerLoadRelation($models, $name, $constraints);
                        }
                    }
                }
                $result[] = $model;
            } else {
                app('trace')->info(TraceCode::ASV_EAGER_LOAD_IMPLEMENTATION, [
                    "name" => $name,
                    "method" => $method,
                    "reason" => "Method implementation missing"
                ]);
                // Fallback to the parent method if the method does not exist
                return parent::eagerLoadRelation($models, $name, $constraints);
            }
        }
        return $result;
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
