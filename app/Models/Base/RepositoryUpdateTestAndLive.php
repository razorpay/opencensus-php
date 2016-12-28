<?php

namespace RZP\Models\Base;

use Config;
use RZP\Exception;

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

        $exists = $entity->exists;

        $this->db->connection('test')->beginTransaction();
        $this->db->connection('live')->beginTransaction();

        try
        {
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
                //
                // The entity hasn't been persisted yet.
                // Create it's copies for live and test database
                //
                $testEntity = clone $entity;
                // we do not care about audit actions for test entities at this point
                $testEntity->resetAuditAction();

                $liveEntity = clone $entity;
            }

            // Persist the entity in both live and test databases.
            $liveEntity->setConnection('live')->saveOrFail($options);
            $testEntity->setConnection('test')->saveOrFail($options);
        }
        catch (\Exception $e)
        {
            //
            // Some error occurred, rollback now.
            //
            $this->db->connection('live')->rollBack();
            $this->db->connection('test')->rollBack();

            throw $e;
        }

        // Update finished successfully, commit now.
        $this->db->connection('live')->commit();
        $this->db->connection('test')->commit();

        // Now that the entity has been updated in both live and test databases,
        // update the entity (in-memory) passed as argument in this function
        $attributes = $liveEntity->getAttributes();
        $entity->setRawAttributes($attributes, true);
        $entity->exists = true;
    }

    public function sync($entity, $relation, $ids = array())
    {
        $this->db->connection('test')->beginTransaction();
        $this->db->connection('live')->beginTransaction();

        $changes = [];

        try
        {
            //
            // The relationship hasn't been synced yet.
            // Create it's copies for live and test database
            //
            $testEntity = clone $entity;
            $testEntity->resetAuditAction();
            $liveEntity = clone $entity;

            $defaultConnection = Config::get('database.default');

            // Sync the relationship in both live and test databases.
            // In laravel 5.2 there is no way to use the parent
            // model connection in relations because of which this
            // hack is used.
            // This has been fixed in Laravel 5.4 by #16103.
            // We'll use the parent connection once we update to
            // L5.4
            Config::set('database.default', 'live');
            $changes = $liveEntity->$relation()->sync($ids);

            Config::set('database.default', 'test');
            $testEntity->$relation()->sync($ids);
        }
        catch (\Exception $e)
        {
            //
            // Some error occurred, rollback now.
            //
            $this->db->connection('live')->rollBack();
            $this->db->connection('test')->rollBack();

            throw $e;
        }
        finally
        {
            // Revert back the database connection to default.
            Config::set('database.default', $defaultConnection);
        }

        // Update finished successfully, commit now.
        $this->db->connection('live')->commit();
        $this->db->connection('test')->commit();

        $entity = $liveEntity;

        return $changes;
    }

    public function attach($entity, $relation, $id, array $attributes = [], $touch = true)
    {
        $this->db->connection('test')->beginTransaction();
        $this->db->connection('live')->beginTransaction();

        try
        {
            //
            // The relationship hasn't been attached yet.
            // Create it's copies for live and test database
            //
            $testEntity = clone $entity;
            $testEntity->resetAuditAction();
            $liveEntity = clone $entity;

            $defaultConnection = Config::set('database.default');

            // Attach the relationship in both live and test databases.
            Config::set('database.default', 'live');
            $liveEntity->$relation()->attach($id);

            Config::set('database.default', 'test');
            $testEntity->$relation()->attach($id);
        }
        catch (\Exception $e)
        {
            //
            // Some error occurred, rollback now.
            //
            $this->db->connection('live')->rollBack();
            $this->db->connection('test')->rollBack();

            throw $e;
        }
        finally
        {
            Config::set('database.default', $defaultConnection);
        }

        // Update finished successfully, commit now.
        $this->db->connection('live')->commit();
        $this->db->connection('test')->commit();

        $entity = $liveEntity;
    }

    public function delete($entity)
    {
        return $this->manager->transactionOnLiveAndTest(function () use ($entity)
        {
            $testEntity = clone $entity;
            $testEntity->resetAuditAction();
            $liveEntity = clone $entity;

            $res1 = $liveEntity->setConnection('live')->delete();
            $res2 = $testEntity->setConnection('test')->delete();

            if ($res1 !== $res2)
            {
                $this->db->connection('live')->rollBack();
                $this->db->connection('test')->rollBack();
                throw new Exception\RuntimeException(
                    'Delete query on live and test did not give same results. ' .
                    'Live: ' . $res1 . ' Test: ' . $res2);
            }

            return $res1;
        });
    }

    public function forceDelete($entity)
    {
        return $this->manager->transactionOnLiveAndTest(function () use ($entity)
        {
            $testEntity = clone $entity;
            $testEntity->resetAuditAction();
            $liveEntity = clone $entity;

            $res1 = $testEntity->forceDelete();
            $res2 = $liveEntity->forceDelete();

            if ($res1 !== $res2)
            {
                throw new Exception\RuntimeException(
                    'Force delete query on live and test did not give same results. ' .
                    'Live: ' . $res1 . ' Test: ' . $res2);
            }

            return $res1;
        });
    }

    protected function dualUpdateFetchEntities($entity)
    {
        $id = $entity->getKey();

        // fetch existing audit action
        $auditAction = $entity->getAuditAction();

        $testEntity = $this->newQueryWithConnection('test')->lockForUpdate()->findOrFail($id);
        $liveEntity = $this->newQueryWithConnection('live')->lockForUpdate()->findOrFail($id);

        $testAttributes = $testEntity->getAttributes();
        $liveAttributes = $liveEntity->getAttributes();

        // reset the current entity's audit action with the older one
        $liveEntity->setAuditAction($auditAction);
        $testEntity->resetAuditAction();

        // Timestamps are allowed to be different
        // Ignore timestamps for similarity.
        unset(
            $testAttributes['created_at'],
            $testAttributes['updated_at'],
            $liveAttributes['created_at'],
            $liveAttributes['updated_at']);

        $diff1 = array_diff_assoc($testAttributes, $liveAttributes);
        $diff2 = array_diff_assoc($liveAttributes, $testAttributes);

        $diff = false;
        $msg = '';

        if (count($diff1) > 0)
        {
            $msg .= print_r($diff1, true) . PHP_EOL;
            $diff = true;
        }
        if (count($diff2) > 0)
        {
            $msg .= print_r($diff2, true) . PHP_EOL;
            $diff = true;
        }

        if ($diff)
        {
            $msg = 'Entity: ' . $this->entity . PHP_EOL . $msg;
            $msg = 'A row in test and live database do not match' . PHP_EOL . $msg;
            throw new Exception\LogicException($msg);
        }

        // Update the test and live entities
        $attributes = $entity->getAttributes();

        $testEntity->setRawAttributes($attributes);
        $liveEntity->setRawAttributes($attributes);

        return array($testEntity, $liveEntity);
    }
}
