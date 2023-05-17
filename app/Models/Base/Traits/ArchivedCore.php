<?php

namespace RZP\Models\Base\Traits;

use App;
use Throwable;

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
    private function newQueryAndResetEntityConnection(callable $callback)
    {
        $entity = $this->getEntityObject();

        $originalConnectionName = $entity->getConnectionName();

        $entity = $callback($this);

        $entity->setConnection($originalConnectionName);

        return $entity;
    }

    /**
     * @throws Throwable
     */
    public function findByPublicIdArchived($id, string $connectionType = null)
    {
        try
        {
            return parent::findByPublicId($id, $connectionType);
        }
        catch (Throwable $e)
        {
            $logData = [
                'id'     => $id,
                'caller' => __FUNCTION__,
            ];

            if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
            {
                $this->tracePreQueryMetrics(__FUNCTION__);

                $queryStartTime = millitime();

                $entity = $this->newQueryAndResetEntityConnection(function() use ($id)
                {
                    return parent::findByPublicId($id,ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function findByPublicIdAndMerchantArchived(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        try
        {
            return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (Throwable $e)
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
                    return parent::findByPublicIdAndMerchant($id, $merchant, $params,ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchantArchived(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        try
        {
            return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (Throwable $e)
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
                    return parent::findByIdAndMerchant($id, $merchant, $params,ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function findByIdAndMerchantIdArchived($id, $merchantId, string $connectionType = null)
    {
        try
        {
            return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
        }
        catch (Throwable $e)
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
                    return parent::findByIdAndMerchantId($id, $merchantId, ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function findOrFailByPublicIdWithParamsArchived($id, array $params, string $connectionType = null): PublicEntity
    {
        try
        {
            return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
        }
        catch (Throwable $e)
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
                    return parent::findOrFailByPublicIdWithParams($id, $params, ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function findOrFailPublicArchived($id, $columns = array('*'), string $connectionType = null)
    {
        try
        {
            return parent::findOrFailPublic($id, $columns, $connectionType);
        }
        catch (Throwable $e)
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
                    return parent::findOrFailPublic($id, $columns, ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
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
                'id'     => $id,
                'caller' => __FUNCTION__,
            ];

            if ($this->isArchivalFallbackEnabledViaEnv($logData) === true)
            {
                $this->tracePreQueryMetrics(__FUNCTION__);

                $queryStartTime = millitime();

                $entity = $this->newQueryAndResetEntityConnection(function() use ($id, $columns)
                {
                    return parent::findOrFail($id, $columns, ConnectionType::ARCHIVED_DATA_REPLICA);
                });

                $entity->setArchived(true);

                $this->tracePostQueryMetrics(__FUNCTION__, $entity, $queryStartTime);

                return $entity;
            }

            throw $e;
        }
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
}
