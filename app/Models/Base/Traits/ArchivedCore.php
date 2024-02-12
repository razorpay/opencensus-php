<?php

namespace RZP\Models\Base\Traits;

use App;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use Throwable;
use RZP\Exception;
use Database\Connection;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Constants\Metric;
use RZP\Base\ConnectionType;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;

trait ArchivedCore
{
    use ArchivedEntity;

    // This is used to reset entity connection post replica fetch to make writes seamless if any, post this operation
    public function newQueryAndResetEntityConnection(callable $callback)
    {
        $entity = $this->getEntityObject();

        $originalConnectionName = $entity->getConnectionName();

        $entity = $callback($this);

        if ($entity !== null)
        {
            $entity->setConnection($originalConnectionName);
        }

        return $entity;
    }

    /**
     * @throws Throwable
     */
    public function findByPublicIdArchived($id)
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function () use ($id)
            {
                return parent::findByPublicId($id, $this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findByPublicIdAndMerchantArchived(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): PublicEntity
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $merchant, $params)
            {
                return parent::findByPublicIdAndMerchant($id, $merchant, $params,$this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchantArchived(
        string $id,
        Merchant\Entity $merchant,
        array $params = []): PublicEntity
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $merchant, $params)
            {
                return parent::findByIdAndMerchant($id, $merchant, $params,$this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchantIdArchived($id, $merchantId)
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $merchantId)
            {
                return parent::findByIdAndMerchantId($id, $merchantId, $this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailByPublicIdWithParamsArchived($id, array $params): PublicEntity
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $params)
            {
                return parent::findOrFailByPublicIdWithParams($id, $params, $this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailPublicArchived($id, $columns = array('*'))
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $columns)
            {
                return parent::findOrFailPublic($id, $columns, $this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailArchived($id, $columns = array('*'), string $connectionType = null)
    {
        try
        {
            return parent::findOrFail($id, $columns, $connectionType);
        }
        catch (Throwable $e)
        {
            $logData = [
                'id' => $id,
                'caller' => __FUNCTION__,
            ];

            if ($this->isArchivalFallbackEnabledViaEnv($logData) === true) {
                $this->tracePreQueryMetrics(__FUNCTION__);

                $queryStartTime = millitime();

                $entity = $this->newQueryAndResetEntityConnection(function () use ($id, $columns)
                {
                    return parent::findOrFail($id, $columns, $this->getArchivedEntityConnection());
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }
        }

        $this->throwInvalidIdException($id);
    }

    /**
     * @throws Throwable
     */
    public function findOrFailOnlyArchived($id, $columns = array('*'))
    {
        $logData = [
            'id'     => $id,
            'caller' => __FUNCTION__,
        ];

        if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
        {
            $this->tracePreQueryMetrics(__FUNCTION__);

            $queryStartTime = millitime();

            $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $columns)
            {
                return parent::findOrFail($id, $columns, $this->getArchivedEntityConnection());
            });

            $entity->setArchived(true);

            $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

            return $entity;
        }

        $this->throwInvalidIdException($id);
    }

    private function isArchivalFallbackEnabledViaEnv(array $logData = []) : bool
    {
        $app = App::getFacadeRoot();

        $entityName = $this->entity;

        if (empty($entityName) === true)
        {
            return false;
        }

        // Use this post archival and gaining confidence of all flows working fine
        // Else use this flow via ENV key for easier reverts
        if (Entity::archivedEntityDbFallbackEnabled($entityName) === true)
        {
            return true;
        }

        $archivalFallbackEnvKey = 'ENABLE_QUERY_FALLBACK_ON_ARCHIVED_' . strtoupper($entityName);

        $archivalFallbackEnvValue = getenv($archivalFallbackEnvKey);

        $archivalFallbackConfigEnabled = false;

        $isWorkerPod = ($app->runningInQueue() === true);

        // Loading from config key in workers
        if ($isWorkerPod === true)
        {
            $archivalFallbackConfigEnabled = $this->isArchivalFallbackConfigKeyEnabled($entityName);
        }

        // Note : Explicitly setting `==` for $archivalFallbackEnvValue to handle env datatype conversions. Do not change to `===`
        return (($archivalFallbackEnvValue == true) or ($archivalFallbackConfigEnabled === true));
    }

    private function isArchivalFallbackConfigKeyEnabled($entityName): bool
    {
        if (isset(Entity::$archivalFallbackConfigKey[$entityName]) === true)
        {
            $keyName = Entity::$archivalFallbackConfigKey[$entityName];

            return (bool) ConfigKey::get($keyName, false);
        }

        return false;
    }

    private function tracePreQueryMetrics(string $functionName)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::ARCHIVED_ENTITY_FETCH_TOTAL, [
            'caller'      => $functionName,
            'entity_name' => $this->entity,
        ]);
    }

    private function tracePostQueryMetrics(string $functionName, $entity, $queryStartTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->info(TraceCode::ARCHIVED_DATA_FETCH_SUCCESS, [
            'id'          => $entity->getId(),
            'entity_name' => $this->entity,
        ]);

        $trace->count(Metric::ARCHIVED_ENTITY_FETCH_SUCCESS, [
            'caller'      => $functionName,
            'entity_name' => $this->entity,
        ]);

        $trace->histogram(Metric::ARCHIVED_ENTITY_FETCH_TIME_TAKEN, millitime() - $queryStartTime);
    }

    private function throwInvalidIdException($id)
    {
        $data = [
            'model' => $this->entityName,
            'attributes' => $id,
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function getArchivedEntityConnection()
    {
        $experimentResult = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(), RazorxTreatment::ARCHIVED_REPLICA_QUERY_MOVEMENT, Mode::LIVE);

        if ($experimentResult !== 'on')
        {
            return ConnectionType::ARCHIVED_DATA_REPLICA;
        }

        $connection = $this->app->runningUnitTests() ? Connection::LIVE : ConnectionType::PAYMENT_FETCH_REPLICA;

        if ($this->entity !== Entity::PAYMENT)
        {
            $connection = ConnectionType::ARCHIVED_DATA_REPLICA;
        }

        return $connection;
    }
}
