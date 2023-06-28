<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use App;
use Razorpay\Asv\RequestMetadata;
use Razorpay\Trace\Logger;
use Razorpay\Asv\Client as ASVClient;
use Razorpay\Asv\Error\GrpcError;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant;
use RZP\Exception;
use Razorpay\Asv\DbSource;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Trace\TraceCode;

class Base
{
    protected $app;

    /** @var Logger */
    protected $trace;

    /** @var ASVClient */
    protected ASVClient $asvSdkClient;

    protected $asvConfig;


    function __construct()
    {
        $app = App::getFacadeRoot();

        $this->app = $app;

        $this->trace = $app[Constant::TRACE];

        $this->asvSdkClient = $app[Constant::ASV_SDK_CLIENT];

        $this->asvConfig = $app->config->get(ASVV2Constant::ASV_CONFIG);
    }

    /**
     * @return ASVClient
     */
    function getAsvSdkClient() {
        return  $this->asvSdkClient;
    }

    function getDefaultRequestMetaData(): RequestMetadata {
        $requestMetadata = new RequestMetadata();
        $requestMetadata->setSourceDatabase(DbSource::ApiMaster);
        $requestMetadata->setTimeoutInMicroSeconds($this->asvConfig[ASVV2Constant::GRPC_TIMEOUT]);
        try {
            $requestMetadata->setRequestId($this->app['request']->getId());
            $requestMetadata->setTaskId($this->app['request']->getTaskId());
            // TODO: Add trace id
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::ACCOUNT_SERVICE_REQUEST_METADATA_ERROR, [
                    'err' => $e->getMessage(),
                ]
            );
        }

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

    /**
     * @throws BadRequestException
     * @throws BaseException|\Exception
     */
    public function getLatestByMerchantId(string $id, RequestMetadata $requestMetadata = null): ?PublicEntity
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

    public function getLatestByMerchantIdCallBack(string $id, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function() use ($id, $requestMetadata) {
            return $this->getLatestByMerchantId($id, $requestMetadata);
        };
    }
}
