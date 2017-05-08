<?php

namespace RZP\Models\Base;

use Config;
use RZP\Exception;
use RZP\Constants\Mode;

trait RepositoryUpdateTestAndLive
{
    /**
     * Save the model to the database.
     *
     * @param  array  $options
     */
    public function saveOrFail($entity, array $options = array())
    {
        $this->validateInstanceIsOfCurrentEntity($entity);
        $this->validateIdGenerated($entity);

        $liveEntity = $this->repo->transactionOnLiveAndTest(
            function () use ($entity, $options)
            {
                $exists = $entity->exists;

                if ($exists)
                {
                    //
                    // The entity already exists in db
                    // Fetch it from both live and test databases
                    // and lock for update
                    //

                    list($testEntity, $liveEntity) = $this->dualUpdateFetchEntities($entity);
                }
                else
                {
                    list($liveEntity, $testEntity) = $this->cloneEntity($entity);
                }

                // Persist the entity in both live and test databases.
                $liveEntity->saveOrFail($options);
                $testEntity->saveOrFail($options);

                $this->validateEntitiesMatch($liveEntity, $testEntity);

                return $liveEntity;
            });

        // Now that the entity has been updated in both live and test databases,
        // update the entity (in-memory) passed as argument in this function
        $attributes = $liveEntity->getAttributes();
        $entity->setRawAttributes($attributes, true);
        $entity->exists = true;
    }

    public function sync($entity, $relation, $ids = array())
    {
        return $this->repo->transactionOnLiveAndTest(
            function () use ($entity, $relation, $ids)
            {
                $changes = [];

                //
                // The relationship hasn't been synced yet.
                // Create it's copies for live and test database
                //
                list($liveEntity, $testEntity) = $this->cloneEntity($entity);

                // Sync the relationship in both live and test databases.
                // In laravel 5.2 there is no way to use the parent
                // model connection in relations because of which this
                // hack is used.
                // This has been fixed in Laravel 5.4 by #16103.
                // We'll use the parent connection once we update to
                // L5.4
                Config::set('database.default', Mode::LIVE);
                $changes = $liveEntity->$relation()->sync($ids);

                Config::set('database.default', Mode::TEST);
                $testEntity->$relation()->sync($ids);

                return $changes;
            });
    }

    public function detach($entity, $relation, $ids = [])
    {
        return $this->repo->transactionOnLiveAndTest(
            function () use ($entity, $relation, $ids)
            {
                $changes = [];

                //
                // The relationship hasn't been synced yet.
                // Create it's copies for live and test database
                //
                list($liveEntity, $testEntity) = $this->cloneEntity($entity);

                // Detach the relationships in both live and test
                Config::set('database.default', Mode::LIVE);
                $liveDetachedEntitiesCount = $liveEntity->$relation()->detach($ids);

                Config::set('database.default', Mode::TEST);
                $testDetachedEntitiesCount = $testEntity->$relation()->detach($ids);

                return $changes;
            });
    }

    public function attach(
        $entity, $relation,
        array $ids = [],
        array $attributes = [], $touch = true)
    {
        return $this->repo->transactionOnLiveAndTest(
            function () use ($entity, $relation, $ids, $touch)
            {
                //
                // The relationship hasn't been attached yet.
                // Create it's copies for live and test database
                //
                list($liveEntity, $testEntity) = $this->cloneEntity($entity);

                // Attach the relationship in both live and test databases.
                Config::set('database.default', Mode::LIVE);
                $liveEntity->$relation()->attach($ids);

                Config::set('database.default', Mode::TEST);
                $testEntity->$relation()->attach($ids);
            });
    }

    public function delete($entity)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            list($liveEntity, $testEntity) = $this->cloneEntity($entity);

            $res1 = $liveEntity->delete();
            $res2 = $testEntity->delete();

            $this->validateEntitiesMatch($liveEntity, $testEntity);

            return $res1;
        });
    }

    public function deleteOrFail($entity)
    {
        $deleted = static::delete($entity);

        if ($deleted === false)
        {
            $this->processDbQueryFailure('delete');
        }
    }

    public function forceDelete($entity)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($entity)
        {
            list($liveEntity, $testEntity) = $this->cloneEntity($entity);

            $res1 = $testEntity->forceDelete();
            $res2 = $liveEntity->forceDelete();

            $this->validateEntitiesMatch($liveEntity, $testEntity);

            return $res1;
        });
    }

    protected function dualUpdateFetchEntities($entity)
    {
        $id = $entity->getKey();

        // fetch existing audit action
        $auditAction = $entity->getAuditAction();

        $testEntity = $this->newQueryWithConnection(Mode::TEST)->lockForUpdate()->findOrFail($id);
        $liveEntity = $this->newQueryWithConnection(Mode::LIVE)->lockForUpdate()->findOrFail($id);

        // reset the current entity's audit action with the older one
        $liveEntity->setAuditAction($auditAction);
        $testEntity->resetAuditAction();

        $this->validateEntitiesMatch($liveEntity, $testEntity);

        // Update the test and live entities
        $attributes = $entity->getAttributes();

        $testEntity->setRawAttributes($attributes);
        $liveEntity->setRawAttributes($attributes);

        $testEntity->setConnection(Mode::TEST);
        $liveEntity->setConnection(Mode::LIVE);

        return array($testEntity, $liveEntity);
    }

    protected function cloneEntity($entity)
    {
        $testEntity = clone $entity;
        $testEntity->resetAuditAction();
        $liveEntity = clone $entity;

        $liveEntity->setConnection(Mode::LIVE);
        $testEntity->setConnection(Mode::TEST);

        return [$liveEntity, $testEntity];
    }

    protected function validateEntitiesMatch($liveEntity, $testEntity)
    {
        $testAttributes = $testEntity->getAttributes();
        $liveAttributes = $liveEntity->getAttributes();

        // Timestamps are allowed to be different
        // Ignore timestamps for similarity.
        unset(
            $testAttributes['created_at'],
            $testAttributes['updated_at'],
            $testAttributes['deleted_at'],
            $liveAttributes['created_at'],
            $liveAttributes['updated_at'],
            $liveAttributes['deleted_at']);

        $diff1 = array_diff_assoc($testAttributes, $liveAttributes);
        $diff2 = array_diff_assoc($liveAttributes, $testAttributes);

        $diff = false;
        $msg = '';

        if (count($diff1) > 0)
        {
            $msg .= json_encode($diff1) . PHP_EOL;
            $diff = true;
        }
        if (count($diff2) > 0)
        {
            $msg .= json_encode($diff2) . PHP_EOL;
            $diff = true;
        }

        if ($diff)
        {
            $msg = 'Entity: ' . $this->entity . PHP_EOL . $msg;
            $msg .= '. A row in test and live database do not match' . PHP_EOL;

            throw new Exception\LogicException($msg);
        }
    }
}
