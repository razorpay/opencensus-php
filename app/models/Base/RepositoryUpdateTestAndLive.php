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
        if (get_class($entity) !== $this->repo)
        {
            throw new Exception\LogicException(
                'Can only handle ' . $this->repo . ' entities here. Provided: ' . get_class($entity));
        }

        $id = $entity->getKey();

        $exists = $entity->exists;

        DB::connection('test')->beginTransaction();
        DB::connection('live')->beginTransaction();

        try
        {
            if ($exists)
            {
                // The entity already exists in db
                // Fetch it from both live and test databases
                // and lock for update

                $repo = $this->repo;
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
            }
            else
            {
                $testEntity = clone $entity;
                $liveEntity = clone $entity;
            }

            $testEntity->setConnection('test')->saveOrFail($options);
            $liveEntity->setConnection('live')->saveOrFail($options);
        }
        catch (\Exception $e)
        {
            DB::connection('live')->rollBack();
            DB::connection('test')->rollBack();

            throw $e;
        }

        DB::connection('live')->commit();
        DB::connection('test')->commit();

        $entity->exists = true;
    }

}