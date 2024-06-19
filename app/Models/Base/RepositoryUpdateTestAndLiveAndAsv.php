<?php

namespace RZP\Models\Base;

use Config;
use Database\Connection;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Constants\Entity as ConstantEntity;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Base as AsvSdkIntegration;
use RZP\Models\Merchant;
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
        $this->overrideDeactivateIfApplicable($entity);

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

                list($liveEntity, $testEntity, $asvEntity) = $this->removeAsvFieldsFromEntity($liveEntity, $testEntity, $asvEntity);
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

    protected function removeAsvFieldsFromEntity($liveEntity, $testEntity, $asvEntity): array
    {
        $entityName =  $liveEntity->getEntityName();
        switch($entityName) {
            case "merchant_detail" :
                unset($liveEntity['edd_verification_status'],
                      $testEntity['edd_verification_status'],
                      $asvEntity['edd_verification_status'],
                      $liveEntity['details'],
                      $testEntity['details'],
                      $asvEntity['details']);
                break;
            case "merchant_business_detail" :
                unset($liveEntity['products'],
                      $testEntity['products'],
                      $asvEntity['products']);
        }

        return array($liveEntity, $testEntity, $asvEntity);
    }

    // note: this is a temporary fix to solve the following issue: https://razorpay.slack.com/archives/C027FDDSZ0F/p1716188016901139
    // context: only for the race condition pattern we are observing all 3 columns: activated_at, activated, live to get unset.
    // hence to handle the race condition, we will override this behaviour behind an experiment after log verification.
    // during regular deactivate: only activated, live are unset.
    private function overrideDeactivateIfApplicable($entity)
    {
        try {
            if ($entity->getEntityName() !== ConstantEntity::MERCHANT) {
                return;
            }

            $dirtyChanges = $entity->getDirty();

            $originalData = $entity->getOriginal();

            if ((count($dirtyChanges) > 0)
                and array_key_exists(MerchantEntity::ACTIVATED_AT, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::ACTIVATED_AT, $originalData) === true
                and array_key_exists(MerchantEntity::ACTIVATED, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::ACTIVATED, $originalData) === true
                and array_key_exists(MerchantEntity::LIVE, $dirtyChanges) === true
                and array_key_exists(MerchantEntity::LIVE, $originalData) === true
                and $originalData[MerchantEntity::ACTIVATED_AT] !== null
                and $dirtyChanges[MerchantEntity::ACTIVATED_AT] === null
                and $originalData[MerchantEntity::ACTIVATED] === true
                and $dirtyChanges[MerchantEntity::ACTIVATED] === 0
                and $originalData[MerchantEntity::LIVE] === true
                and $dirtyChanges[MerchantEntity::LIVE] === 0
            ) {
                app('trace')->info(TraceCode::ACTIVATION_FIELDS_UNSET, [
                    'dirty_changes' => $dirtyChanges,
                    'merchant_id' => $entity->getId(),
                    'original_data' => $originalData,
                ]);

                $this->getDebugBackTrace();

                if ((new Merchant\Core)->isSplitzExperimentEnable(
                        [
                            'id' => app('request')->getTaskId(),
                            'experiment_id' => app('config')->get('app.override_deactivate_experiment_id'),
                        ],
                        'enable'
                    ) === false) {
                    return;
                }

                $entity->setAttribute(MerchantEntity::ACTIVATED_AT, $originalData[MerchantEntity::ACTIVATED_AT]);
                $entity->setAttribute(MerchantEntity::ACTIVATED, $originalData[MerchantEntity::ACTIVATED]);
                $entity->setAttribute(MerchantEntity::LIVE, $originalData[MerchantEntity::LIVE]);
            }
        }
        catch (\Throwable $exception)
        {
            app('trace')->traceException($exception, Trace::ERROR, TraceCode::ACTIVATION_FIELDS_UNSET_ERROR);
        }
    }

    public function getRouteOrJobName()
    {
        try {
            $runningInQueue = app()->runningInQueue();
            if ($runningInQueue === true) {
                $flow = app('worker.ctx')->getJobName();
            } else {
                $flow = app('request.ctx')->getRoute();
            }

            if ($flow === null or $flow === "") {
                return "none";
            }

            return $flow;

        } catch (\Exception $e) {
            $this->trace->traceException($e, Trace::WARNING, TraceCode::ASV_ROLLBACK_GET_ROUTE_OR_WORKER_NAME_EXCEPTION);
            return "none";
        }
    }

    public function getDebugBackTrace(): void
    {
        try {
            $route = $this->getRouteOrJobName();

            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            $traceInfo = [];

            foreach ($backtrace as $trace) {
                if (isset($trace['file']) && !str_contains($trace['file'], 'vendor/')) {
                    $traceInfo[] = [
                        'line' => $trace['line'],
                        'function' => $trace['function'] ?? 'N/A',
                        'class' => $trace['class'] ?? 'N/A',
                    ];
                }
            }

            app('trace')->info(TraceCode::ACTIVATION_FIELDS_UNSET_DEBUG, [
                "trace" => $traceInfo,
                "route" => $route
            ]);
        } catch (\Exception $e) {
            app('trace')->traceException($e,
                null,
                TraceCode::ACS_ROUTE_QUERY_LOGS_EXCEPTION
            );
        }
    }
}
