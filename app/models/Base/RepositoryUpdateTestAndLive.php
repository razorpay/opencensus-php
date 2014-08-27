<?php

namespace Models\Base;

use DB;
use EE\Exception;

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

        DB::connection('test')->beginTransaction();
        DB::connection('live')->beginTransaction();

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
            DB::connection('live')->rollBack();
            DB::connection('test')->rollBack();

            throw $e;
        }

        // Update finished successfully, commit now.
        DB::connection('live')->commit();
        DB::connection('test')->commit();

        // Now that the entity has been updated in both live and test databases,
        // update the one passed as argument in this function
        $attributes = $liveEntity->getAttributes();
        $entity->setRawAttributes($attributes, true);
        $entity->exists = true;
    }

    protected function dualUpdateVerifyEntityClass($entity)
    {
        if (get_class($entity) !== $this->repo)
        {
            throw new Exception\LogicException(
                'Can only handle ' . $this->repo . ' entities here. Provided: ' . get_class($entity));
        }
    }

    protected function dualUpdateFetchEntities($entity)
    {
        $repo = $this->repo;

        $id = $entity->getKey();

        $testEntity = $repo::on('test')->lockForUpdate()->findOrFail($id);
        $liveEntity = $repo::on('live')->lockForUpdate()->findOrFail($id);

        $testAttributes = $testEntity->getAttributes();
        $liveAttributes = $liveEntity->getAttributes();

        $diff1 = array_diff_assoc($testAttributes, $liveAttributes);
        $diff2 = array_diff_assoc($liveAttributes, $testAttributes);

        if ((count($diff1) > 0) or
            (count($diff2) > 0))
        {
            throw new Exception\LogicException('A row in test and live database do not match');
        }

        // Update the test and live entities
        $attributes = $entity->getAttributes();

        $testEntity->setRawAttributes($attributes);
        $liveEntity->setRawAttributes($attributes);

        return array($testEntity, $liveEntity);
    }
}