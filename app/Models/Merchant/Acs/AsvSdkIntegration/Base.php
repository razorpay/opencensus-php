<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use App;
use Razorpay\Asv\RequestMetadata;
use Razorpay\Trace\Logger as Trace;
use Razorpay\Asv\Client as ASVClient;
use Razorpay\Asv\Error\GrpcError;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Http\RequestHeader;
use RZP\Models\Base\Audit\Constants;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant;
use RZP\Exception;
use Razorpay\Asv\DbSource;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\EntityToProtoConverter\Factory;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto\Factory as GetFieldsForEntityFromProtoFactory;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\GetFieldsForEntityFromProto\GetFieldsForEntityFromProtoInterface;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\RequestHeadersHelper\RequestHeadersHelper;
use RZP\Models\Merchant\Website\Entity;
use RZP\Trace\TraceCode;
use RZP\lib\AwsTraceIdExtractor;
use RZP\Constants\Metric;
use RZP\Models\Base as BaseModel;

class Base
{
    protected $app;

    /** @var Logger */
    protected $trace;

    /** @var ASVClient */
    protected ASVClient $asvSdkClient;

    protected $asvConfig;

    protected $awsTraceIdExtractor;

    const AUDIT_ID_KEY = 'audit_id';

    const SAVE_TIMEOUT_IN_MICRO_SECONDS = 2000000;

    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->awsTraceIdExtractor = new AwsTraceIdExtractor();

        $this->asvSdkClient = $app[Constant::ASV_SDK_CLIENT];

        $this->asvConfig = $app->config->get(ASVV2Constant::ASV_CONFIG);
    }

    /**
     * @return ASVClient
     */
    function getAsvSdkClient(): ASVClient
    {
        return  $this->asvSdkClient;
    }

    function getDefaultRequestMetaData(): RequestMetadata {
        $requestMetadata = new RequestMetadata();
        $requestMetadata->setSourceDatabase(DbSource::ApiMaster);
        $requestMetadata->setTimeoutInMicroSeconds($this->asvConfig[ASVV2Constant::GRPC_TIMEOUT]);
        $awsTraceId = "";
        try {
            $requestMetadata->setRequestId($this->app['request']->getId());
            $requestMetadata->setTaskId($this->app['request']->getTaskId());
            $awsTraceId = $this->awsTraceIdExtractor->getAwsTraceId();
            // TODO: Add trace id
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_REQUEST_METADATA_ERROR, [
                    'err' => $e->getMessage(),
                ]
            );
        }

        $headers = [RequestHeader::X_AMAZON_TRACE_ID => $awsTraceId];
        $requestMetadata->setHeaders($headers);

        return $requestMetadata;
    }

    function getRequestMetaData(RequestMetadata|null $inputRequestMetadata = null) : RequestMetadata {
        $requestMetadata = $this->getDefaultRequestMetaData();

        if (isset($inputRequestMetadata)) {
            if($inputRequestMetadata->getSourceDatabase() != null){
                $requestMetadata->setSourceDatabase($inputRequestMetadata->getSourceDatabase());
            }

            if($inputRequestMetadata->getRequestId() != null) {
                $requestMetadata->setRequestId($inputRequestMetadata->getRequestId());
            }

            if($inputRequestMetadata->getTaskId() != null) {
                $requestMetadata->setTaskId($inputRequestMetadata->getTaskId());
            }

            if($inputRequestMetadata->getTimeoutInMicroSeconds() != null) {
                $requestMetadata->setTimeoutInMicroSeconds($inputRequestMetadata->getTimeoutInMicroSeconds());
            }

            if($inputRequestMetadata->getTraceId() != null) {
                $requestMetadata->setTraceId($inputRequestMetadata->getTraceId());
            }
        }

        return $requestMetadata;
    }

    function getRequestMetaDataForSave(RequestMetadata|null $inputRequestMetadata = null) : RequestMetadata {

        $requestMetaData = $this->getRequestMetaData($inputRequestMetadata);

        // override timeout as 2s for save.
        $requestMetaData->setTimeoutInMicroSeconds(self::SAVE_TIMEOUT_IN_MICRO_SECONDS);

        $currentHeaders = $requestMetaData->getHeaders();

        $saveHeaders = (new RequestHeadersHelper())->getRequestHeaders();

        $headers = array_merge($currentHeaders, $saveHeaders);

        $this->trace->info(TraceCode::ASV_CALL_SYNC_ACCOUNT_DEVIATION, [
            'request_headers' => $headers,
        ]);

        $requestMetaData->setHeaders($headers);

        return $requestMetaData;
    }

    /**
     * @throws \Throwable
     * @throws BaseException
     * @throws BadRequestException
     */
    function save(BaseModel\PublicEntity $entity, ?RequestMetadata $requestMetadata = null): void
    {
        try {

            $dirtyFieldKeys = array_keys($entity->getDirty());

            // if no changed field then we don't have anything to save
            // so don't do any operation
            if (empty($dirtyFieldKeys) === true) {
                $this->trace->warning(TraceCode::ASV_WRITE_CALLED_WITHOUT_DIRTY_FIELDS, [
                    'entity' => $entity->getEntityName(),
                ]);
                return;
            }
            $requestProto = (Factory::
            getEntityToProtoConvertor($entity, $dirtyFieldKeys))->toSaveProtoRequest();

            [$response, $err] = $this->getAsvSdkClient()->getWriteService()->save(
              $requestProto,
              $this->getRequestMetaDataForSave($requestMetadata)
            );


            if ($err !== null) {
                $this->handleError($err);
            }

            $fieldFromProtoHelper = GetFieldsForEntityFromProtoFactory::getEntityToProtoConvertor($entity, $response);
            $this->setEntityAttributes($entity, $fieldFromProtoHelper);
        } catch (\Throwable $e) {

            $this->trace->count(Metric::ASV_WRITE_REQUEST_ERROR, [
                'error_code' => $e->getCode(),
            ]);

            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_ERROR);
            throw $e;
        }
    }

    /**
     * @throws \Throwable
     * @throws BaseException
     * @throws BadRequestException
     */
    function delete(BaseModel\PublicEntity $entity, ?RequestMetadata $requestMetadata = null): void
    {
        try {

            $requestProto = (Factory::
            getEntityToProtoConvertor($entity, []))->toDeleteProtoRequest();

            [$response, $err] = $this->getAsvSdkClient()->getWriteService()->delete(
                $requestProto,
                $this->getRequestMetaDataForSave($requestMetadata)
            );


            if ($err !== null) {
                $this->handleError($err);
            }

        } catch (\Throwable $e) {

            $this->trace->count(Metric::ASV_DELETE_REQUEST_ERROR, [
                'error_code' => $e->getCode(),
            ]);

            $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_DELETE_ERROR);
            throw $e;
        }
    }

    function setEntityAttributes(BaseModel\PublicEntity                $entity,
                                 ?GetFieldsForEntityFromProtoInterface $fieldFromProtoHelper): void
    {
        $entity->setCreatedAt($fieldFromProtoHelper->getCreatedAt());
        $entity->setUpdatedAt($fieldFromProtoHelper->getUpdatedAt());

        if (in_array($entity->getEntityName(),\RZP\Constants\Entity::AUDITED_ENTITIES, true) === true){
            $entity->setAttribute(self::AUDIT_ID_KEY, $fieldFromProtoHelper->getAuditId());
        }
        $entity->setRawAttributes($entity->getAttributes(), true);
        $entity->exists = true;
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function handleError(?GrpcError $err): void
    {
        if ($err != null) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_HANDLE_RESPONSE_ERROR, [
                'error_code' => $err->getCode(),
                'error_message' => $err->getMessage()
            ]);

            throw match ($err->getCode()) {
                \GRPC\STATUS_NOT_FOUND => new Exception\BadRequestException(ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID, null, null, $err->getMessage()),
                \Grpc\STATUS_INVALID_ARGUMENT => new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ARGUMENT, null, null, $err->getMessage()),
                default => new Exception\BaseException($err->getMessage(), ErrorCode::ASV_SERVER_ERROR),
            };
        }
    }

    /**
     * @throws \Exception
     */
    public function getByIdForFindOrFail(
        $id
    ) {

        try {
                return $this->getById($id);
        } catch (\Exception $err) {
                // we ignore validation and not found errors for find function.
                if($err->getCode() == ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID ||
                    $err->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                    // TODO: Add metric and log for when this happens to do the tracking.
                    return null;
                }

                throw $err;
        }
    }

    /**
     * @throws \Exception
     */
    public function getByMerchantIdIgnoreInvalidArgument(string $merchantId, ?RequestMetadata $requestMetadata = null): ?PublicCollection
    {
        try {
            $entityByMerchantId = $this->getByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return new PublicCollection();
            }

            throw $e;
        }

        return $entityByMerchantId;
    }

    public function getByMerchantIdIgnoreInvalidArgumentCallback(string $merchantId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($merchantId, $requestMetadata) {
            return $this->getByMerchantIdIgnoreInvalidArgument($merchantId, $requestMetadata);
        };
    }

    public function findOneByMerchantIdCallback(string $merchantId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($merchantId, $requestMetadata) {
            return $this->findOneByMerchantId($merchantId, $requestMetadata);
        };
    }

    public function findOneByMerchantId($merchantId, $requestMetadata)
    {
        try {
            $entityByMerchantId = $this->getByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if($e->getCode() == ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID ||
                $e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        return $entityByMerchantId[0];
    }

    /**
     * @throws BadRequestException
     * @throws BaseException|\Exception
     */
    public function getLatestByMerchantIdOrFail(string $id, RequestMetadata $requestMetadata = null): ?PublicEntity
    {
        try {
            $entitiesForMerchantId = $this->getByMerchantId($id, $requestMetadata);
        } catch (\Exception $e) {
            if($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        // Account Service, By default returns the ordering by created at desc. To get the latest element
        // we need to return the first element from the response.
        return $entitiesForMerchantId->first();
    }

    public function getLatestByMerchantIdOrFailCallBack(string $id, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($id, $requestMetadata) {
            return $this->getLatestByMerchantIdOrFail($id, $requestMetadata);
        };
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getLatestByMerchantId(string $id, RequestMetadata $requestMetadata = null): ?PublicEntity
    {
        try {
            $merchantWebsitesForMerchantId = $this->getLatestByMerchantIdOrFail($id, $requestMetadata);
        } catch (\Exception $e) {
            if( $e->getCode() == ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID) {
                return null;
            }

            throw $e;
        }

        return $merchantWebsitesForMerchantId;
    }

    public function getLatestByMerchantIdCallBack(string $id, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($id, $requestMetadata) {
            return $this->getLatestByMerchantId($id, $requestMetadata);
        };
    }
}
