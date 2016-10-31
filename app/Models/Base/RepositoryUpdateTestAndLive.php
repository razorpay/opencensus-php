<?php

namespace RZP\Models\Base;

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
        $this->dualUpdateVerifyEntityClass($entity);

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

    public function delete($entity)
    {
        return $this->manager->transactionOnLiveAndTest(function () use ($entity)
        {
            $testEntity = clone $entity;
            $liveEntity = clone $entity;

            $res1 = $liveEntity->delete();
            $res2 = $testEntity->delete();

            if ($res1 !== $res2)
            {
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

    protected function dualUpdateVerifyEntityClass($entity)
    {
        if ($entity->getEntityName() !== $this->entity)
        {
            throw new Exception\LogicException(
                'Can only handle ' . $this->entity . ' entities here. Provided: ' . $entity->getEntityName());
        }
    }

    protected function dualUpdateFetchEntities($entity)
    {
        $id = $entity->getKey();

        $testEntity = $this->newQueryWithConnection('test')->lockForUpdate()->findOrFail($id);
        $liveEntity = $this->newQueryWithConnection('live')->lockForUpdate()->findOrFail($id);

        $testAttributes = $testEntity->getAttributes();
        $liveAttributes = $liveEntity->getAttributes();

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
            ob_start();
            print_r($diff1);
            $msg .= ob_get_clean() . PHP_EOL;
            $diff = true;
        }
        if (count($diff2) > 0)
        {
            ob_start();
            print_r($diff2);
            $msg .= ob_get_clean() . PHP_EOL;
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