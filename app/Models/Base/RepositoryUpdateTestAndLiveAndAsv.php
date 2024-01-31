<?php

namespace RZP\Models\Base;

use Config;
use Database\Connection;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Base as AsvSdkIntegration;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Trace\TraceCode;

trait RepositoryUpdateTestAndLiveAndAsv
{
    use RepositoryUpdateTestAndLive {
        RepositoryUpdateTestAndLive::saveOrFail as saveOrFailInLiveAndTest;
        RepositoryUpdateTestAndLive::delete as deleteInLiveAndTest;
    }
    /**
     * Save the model to the database.
     *
     * @param  PublicEntity  $entity
     * @param  array         $options
     *
     * @throws Exception\LogicException
     */
    public function saveOrFail($entity, array $options = array())
    {
        if ($this->entityShouldSync($entity) === false)
        {
            return parent::saveOrFail($entity, $options);
        }

        $this->validateInstanceIsOfCurrentEntity($entity);

        $this->validateIdGenerated($entity);

        /*
         *  If request is not routed to account service then call parent save or fail
         */
        if ($this->shouldRouteRequestToAccountService($entity, $options, FunctionConstant::SAVE_OR_FAIL) === false)
        {
            return $this->saveOrFailInLiveAndTest($entity, $options);
        }

        $action = $entity->exists ? EsRepository::UPDATE : EsRepository::CREATE;

        $dirty  = $entity->getDirty();

        $liveEntity = $this->repo->transactionOnLiveAndTestAndAsv(
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

                    list($testEntity, $liveEntity, $asvEntity) = $this->asvUpdateFetchEntities($entity);
                }
                else
                {
                    list($liveEntity, $testEntity) = $this->cloneEntity($entity);
                    $asvEntity = $this->createAsvEntity($entity);
                }

                // Persist the entity in both live and test databases.
                $liveEntity->saveOrFail($options);

                $testEntity->saveOrFail($options);

                $asvEntity->saveOrFail($options);

                $this->validateEntitiesMatch($liveEntity, $testEntity);

                return $liveEntity;
            });

        // api test , api live , asv db
        // read asv
        // write to asv db (maxwell sync will happen to api test and api live)

        //
        // Now that the entity has been updated in both live and test databases,
        // update the entity (in-memory) passed as argument in this function
        //
        $attributes = $liveEntity->getAttributes();

        $entity->setRawAttributes($attributes, true);

        $entity->exists = true;

        $this->syncToEsLiveAndTest($entity, $action, $dirty);
    }

    /**
     * @throws \Throwable
     */
    public function delete($entity)
    {
        if ($this->entityShouldSync($entity) === false)
        {
            return parent::delete($entity);
        }

        if ($this->shouldRouteRequestToAccountService($entity, [], FunctionConstant::DELETE_OR_FAIL) === false)
        {
            $this->deleteInLiveAndTest($entity);

            return;
        }

        $res = $this->repo->transactionOnLiveAndTestAndAsv(function () use ($entity)
        {
            list($liveEntity, $testEntity) = $this->cloneEntity($entity);
            $asvEntity = $this->createAsvEntity($entity);

            $res1 = $liveEntity->delete();
            $res2 = $testEntity->delete();
            $asvEntity->delete();

            $this->validateEntitiesMatch($liveEntity, $testEntity);

            return $res1;
        });

        $this->syncToEsLiveAndTest($entity, EsRepository::DELETE);

        return $res;
    }

    public function deleteOrFail($entity)
    {
        $deleted = static::delete($entity);

        if ($deleted === false)
        {
            $this->processDbQueryFailure('delete');
        }
    }

    protected function createAsvEntity($entity)
    {
        $asvEntity = clone $entity;
        $asvEntity->setConnection(Connection::ASV_WRITER);

        return $asvEntity;
    }

    protected function asvUpdateFetchEntities($entity)
    {
        $id = $entity->getKey();

        // fetch existing audit action
        $auditAction = $entity->getAuditAction();

        $testEntity = $this->newQueryWithConnection(Mode::TEST)->lockForUpdate()->findOrFail($id);
        $liveEntity = $this->newQueryWithConnection(Mode::LIVE)->lockForUpdate()->findOrFail($id);
        $asvEntity = $this->newQueryWithConnection(Connection::ASV_WRITER)->lockForUpdate()->findOrFail($id);

        // reset the current entity's audit action with the older one
        $liveEntity->setAuditAction($auditAction);
        $testEntity->resetAuditAction();

        $this->validateEntitiesMatch($liveEntity, $testEntity);

        // Update the test and live entities
        $attributes = $entity->getAttributes();

        $testEntity->setRawAttributes($attributes);
        $liveEntity->setRawAttributes($attributes);
        $asvEntity->setRawAttributes($attributes);

        $testEntity->setConnection(Mode::TEST);
        $liveEntity->setConnection(Mode::LIVE);
        $asvEntity->setConnection(Connection::ASV_WRITER);

        return array($testEntity, $liveEntity, $asvEntity);
    }
}
