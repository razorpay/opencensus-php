<?php

namespace RZP\Models\Merchant\Acs\traits;

use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\RepoToSdkWrapperMap;
use RZP\Exception;

trait AsvFetch {
    public function getEntityDetails(
        string $callingIdentifier,
        bool $shouldRouteToAsv,
        callable $fetchFromAccountServiceCallback,
        callable $fetchFromDatabaseCallback)
    {
        if ($shouldRouteToAsv) {
            try {
                $this->trace->info(TraceCode::ACCOUNT_SERVICE_GET_ENTITY_REQUEST, [
                    "identifier" => $callingIdentifier
                ]);

                return $fetchFromAccountServiceCallback();
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_GET_ENTITY_DETAILS_EXCEPTION, [
                    "identifier" => $callingIdentifier
                ]);
            }
        }

        return $fetchFromDatabaseCallback();
    }

    public function findOrFailDatabase($id, $columns = array('*'), string $connectionType = null)
    {
        return parent::findOrFail($id, $columns, $connectionType);
    }

    public function findOrFailPublicDatabase($id, $columns = array('*'), string $connectionType = null)
    {
        return parent::findOrFailPublic($id, $columns, $connectionType);
    }

    /**
     * @throws \Exception
     */
    public function getDetailsFromAsvIgnoreValidationAndNotFound($id) {
        $asvSdkWrapper = RepoToSdkWrapperMap::getWrapperInstance(get_class($this));

        return $asvSdkWrapper->getByIdForFindOrFail($id);
    }
    public function findOrFailAsv($id)
    {
            $model = $this->getDetailsFromAsvIgnoreValidationAndNotFound($id);

            if($model != null) {
                return $model;
            }

            $this->processDbQueryFailure('find', array('id' => $id, 'columns' => array("*")));
    }

    public function findOrFailPublicAsv($id)
    {

        $model = $this->getDetailsFromAsvIgnoreValidationAndNotFound($id);

        if (is_null($model) === false){
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

    public function findOrFail($id, $columns = array('*'), string $connectionType = null) {

        $shouldCallAsv = (new AsvRouter())->shouldCallAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND_OR_FAIL);

        if($shouldCallAsv) {

            $functionIdentifier = get_class($this)." ".FunctionConstant::FIND_OR_FAIL;

            try {
                return $this->findOrFailAsv($id);
            } catch (\Exception $e) {

                if ($e->getCode() == ErrorCode::SERVER_ERROR_DB_QUERY_FAILED){

                    $this->trace->info(TraceCode::ACCOUNT_SERVICE_THROW_EXCEPTION_AGAIN, [
                        "functionIdentifier" =>  $functionIdentifier,
                        "error_code" => $e->getCode(),
                        "id" => $id,
                    ]);

                    throw $e;
                }

                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_OR_FAIL_EXCEPTION, [
                    "id" =>  $id,
                    "functionIdentifier" =>  $functionIdentifier,
                ]);
            }
        }

        return $this->findOrFailDatabase($id, $columns, $connectionType);
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null) {

        $shouldCallAsv = (new AsvRouter())->shouldCallAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND_OR_FAIL_PUBLIC);

        if($shouldCallAsv) {

            $functionIdentifier = get_class($this)." ".FunctionConstant::FIND_OR_FAIL_PUBLIC;

            try {
                return $this->findOrFailAsv($id);
            } catch (\Exception $e) {

                if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ID){

                    $this->trace->info(TraceCode::ACCOUNT_SERVICE_THROW_EXCEPTION_AGAIN, [
                        "functionIdentifier" =>  $functionIdentifier,
                        "error_code" => $e->getCode(),
                        "id" => $id,
                    ]);

                    throw $e;
                }

                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ACCOUNT_SERVICE_FIND_OR_FAIL_EXCEPTION, [
                    "id" =>  $id,
                    "functionIdentifier" =>  $functionIdentifier,
                ]);
            }
        }

        return $this->findOrFailPublicDatabase($id, $columns, $connectionType);
    }
}
