<?php

namespace RZP\Models\Base;

use Config;
use Database\Connection;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Metric;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
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
        (new Utility())->overrideDeactivateIfApplicable($entity);

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

        $this->findAnomaliesTxn(__FUNCTION__);

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


                $asvEntity->saveOrFail($options);

                $liveEntity->saveOrFail($options);

                $testEntity->saveOrFail($options);

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
     * Checks for anomalies in transactions across specific connections.
     *
     * This method logs the transaction status on live, test, and ASV connections, and prints the stack trace if:
     * - The transaction levels on these connections do not match.
     *
     * @param string $method The name of the method being checked.
     * @return void
     */
    public function findAnomaliesTxn(string $method): void
    {
        try {
            $this->logTxnLevelMismatch($method);
        }
        catch (\Exception $e) {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXCEPTION_IN_TXN_CHECKS, [
                'info' => "Exception during anomalies check on ".$method." db",
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function logTxnLevelMismatch(string $method): void
    {
        $txnLevelOnLive = $this->transactionLevelOnConnection(Mode::LIVE);
        $txnLevelOnAsv = $this->transactionLevelOnConnection(Connection::ASV_WRITER);
        $txnLevelOnTest = $this->transactionLevelOnConnection( Mode::TEST);

        if (($txnLevelOnAsv === $txnLevelOnLive and $txnLevelOnTest === $txnLevelOnAsv) === false)
        {
            $stackTrace = $this->getStackTrace();

            $logDimensions = [
                "txnLevelOnLive" => $txnLevelOnLive,
                "txnLevelOnAsv" => $txnLevelOnAsv,
                "txnLevelOnTest" => $txnLevelOnTest,
                "method" => $method,
                "stackTrace" => $stackTrace
            ];

            $this->trace->info(TraceCode::TXN_LEVELS_MISMATCH, $logDimensions);
            $this->trace->count(Metric::TXN_LEVELS_MISMATCH);
        }
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

        $this->findAnomaliesTxn(__FUNCTION__);

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

    private function getStackTrace(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $traceLines = [];

        foreach ($backtrace as $trace) {
            $traceLines[] = sprintf(
                '%s:%d %s',
                $trace['file'] ?? 'unknown file',
                $trace['line'] ?? 'unknown line',
                $trace['function'] ?? 'unknown function'
            );
        }

        return implode("\n", $traceLines);
    }
}
