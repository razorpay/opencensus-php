<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Cache;
use Database\Connection;
use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Constants;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\RepoToSdkWrapperMap;
use RZP\Exception;

trait AsvFind
{
    use AsvCacheKeys {
        AsvCacheKeys::getCacheKey as getAsvCacheKey;
    }

    public function findOrFailDatabase($id, $columns = array('*'), string $connectionType = null, $oldConnection = null)
    {
        $model = parent::findOrFail($id, $columns, $connectionType);
        $this->setOldConnection($model, $oldConnection);
        return $model;
    }

    public function findOrFailPublicDatabase($id, $columns = array('*'), string $connectionType = null, $oldConnection = null)
    {
        $model =  parent::findOrFailPublic($id, $columns, $connectionType);
        $this->setOldConnection($model, $oldConnection);
        return $model;
    }

    /**
     * @throws \Exception
     */
    public function getDetailsFromAsvIgnoreValidationAndNotFound($id, $oldConnection = null)
    {
        $asvSdkWrapper = RepoToSdkWrapperMap::getWrapperInstance(get_class($this));

        $model = $asvSdkWrapper->getByIdForFindOrFail($id);

        $this->setOldConnection($model, $oldConnection);

        $this->trace->count(Metric::ASV_READ_REQUEST_ROUTING_RESULT, [
            'source' => Constants::ASV_SERVICE,
            'route' => (new AsvRouter())->getRouteOrJobName(),
        ]);

        return $model;
    }

    /**
     * @throws \Exception
     */
    public function findOrFailAsv($id, $oldConnection = null)
    {

        $model = $this->getDetailsFromAsvIgnoreValidationAndNotFound($id, $oldConnection);

        if ($model != null) {
            return $model;
        }

        $this->processDbQueryFailure('find', array('id' => $id, 'columns' => array("*")));
    }

    /**
     * @throws BadRequestException
     * @throws \Exception
     */
    public function findOrFailPublicAsv($id)
    {

        $model = $this->getDetailsFromAsvIgnoreValidationAndNotFound($id);

        if (is_null($model) === false) {
            return $model;
        }

        $data = [
            'model' => $this->getEntityClass(),
            'attributes' => $id,
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $oldConnection = $connectionType;
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND_OR_FAIL);

        if ($shouldCallAsv === true) {

            if ($this->isTransactionActive()) {
                $connectionType = Connection::ASV_WRITER;
            } else {

                $functionIdentifier = get_class($this) . " " . FunctionConstant::FIND_OR_FAIL;

                try {
                    return $this->findOrFailAsv($id, $oldConnection);
                } catch (\Exception $e) {

                    if ($e->getCode() == ErrorCode::SERVER_ERROR_DB_QUERY_FAILED) {

                        $this->trace->info(TraceCode::ACCOUNT_SERVICE_THROW_EXCEPTION_AGAIN, [
                            "functionIdentifier" => $functionIdentifier,
                            "error_code" => $e->getCode(),
                            "id" => $id,
                        ]);

                        throw $e;
                    }

                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_OR_FAIL_EXCEPTION, [
                        "id" => $id,
                        "functionIdentifier" => $functionIdentifier,
                    ]);
                }
            }
        }

        return $this->findOrFailDatabase($id, $columns, $connectionType, $oldConnection);
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $oldConnection = $connectionType;
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND_OR_FAIL_PUBLIC);

        if ($shouldCallAsv === true) {
            if ($this->isTransactionActive()) {
                $connectionType = Connection::ASV_WRITER;
            } else {

                $functionIdentifier = get_class($this) . " " . FunctionConstant::FIND_OR_FAIL_PUBLIC;

                try {
                    return $this->findOrFailAsv($id, $oldConnection);
                } catch (\Exception $e) {

                    if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ID) {

                        $this->trace->info(TraceCode::ACCOUNT_SERVICE_THROW_EXCEPTION_AGAIN, [
                            "functionIdentifier" => $functionIdentifier,
                            "error_code" => $e->getCode(),
                            "id" => $id,
                        ]);

                        throw $e;
                    }

                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_OR_FAIL_EXCEPTION, [
                        "id" => $id,
                        "functionIdentifier" => $functionIdentifier,
                    ]);
                }
            }
        }

        return $this->findOrFailPublicDatabase($id, $columns, $connectionType, $oldConnection);
    }

    public function findDatabase($id, $columns = array('*'), string $connectionType = null, string $oldConnection = null)
    {
        $model = parent::find($id, $columns, $connectionType);
        $this->setOldConnection($model, $oldConnection);
        return $model;
    }

    public function setOldConnection($entity, $oldConnection)
    {
        try
        {
            if($oldConnection === null)
            {
                $oldConnection = $this->connection;
            }

            if($entity instanceof EloquentModel)
            {
                $entity->setConnection($oldConnection);
            } else {
                $this->trace->info(TraceCode::MODEL_NOT_ELOQUENT_INSTANCE, [
                    "route" => app()->runningInQueue() ? app('worker.ctx')->getJobName() : app('request.ctx')->getRoute()
                ]);
            }
        } catch (\Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR ,TraceCode::SET_PARENT_MODE_FAILURE);
        }
    }

    public function findForImplicitJoin($id, string $entityName, $columns = array('*'), string $connectionType = null)
    {
        $shouldCallAsv = $this->asvRouter->shouldRouteFindForImplicitJoinToAccountService($id, $entityName, $columns, $connectionType, get_class($this), FunctionConstant::FIND_FOR_IMPLICIT_JOIN);
        if ($shouldCallAsv === true) {
            $shouldCacheResults = in_array($this->entity, ["merchant", "merchant_detail"]);
            if ($shouldCacheResults === true) {
                return Cache::store('query_cache_live')
                    ->tags(strtolower($this->entity) . '_' . $id)
                    ->remember(
                        $this->getAsvCacheKey($id, $columns, $connectionType),
                        $this->getCacheTtl(),
                        function () use ($id, $columns, $connectionType) {
                            return $this->getResultForImplicitJoin($id, $columns, $connectionType, $connectionType, true);
                        }
                    );
            } else {
                return $this->getResultForImplicitJoin($id, $columns, $connectionType, $connectionType, true);
            }
        }
        return $this->getResultForImplicitJoin($id, $columns, $connectionType, $connectionType, false);
    }

    public function getResultForImplicitJoin($id, $columns, $connectionType, $oldConnection, $shouldCallAsv) {
        if ($shouldCallAsv === true) {
            if ($this->isTransactionActive()) {
                $connectionType = Connection::ASV_WRITER;
            } else {
                $functionIdentifier = get_class($this) . " " . FunctionConstant::FIND_FOR_IMPLICIT_JOIN;
                try {
                    return $this->getDetailsFromAsvIgnoreValidationAndNotFound($id, $oldConnection);
                } catch (\Exception $e) {
                    $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_OR_FAIL_EXCEPTION, [
                        "id" => $id,
                        "functionIdentifier" => $functionIdentifier,
                    ]);
                }
            }
        }

        return $this->findDatabase($id, $columns, $connectionType, $oldConnection);
    }

    public function findForWrite($id, $columns = array('*'), string $connectionType = null)
    {
        try
        {
            return $this->findOrFail($id, $columns, $connectionType);
        } catch (\Exception $e)
        {
            if ($e->getCode() == ErrorCode::SERVER_ERROR_DB_QUERY_FAILED)
            {
                return null;
            }
            throw $e;
        }
    }

}

