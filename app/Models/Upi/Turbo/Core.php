<?php

namespace RZP\Models\Upi\Turbo;

use Monolog\Logger;

use RZP\Models\Base;
use RZP\Error\Error;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Exception\ServerErrorException;
use RZP\Jobs\UpiTurboErrorMappingUpdater;

class Core extends Base\Core
{
    /** @var Utils|null  */
    protected $utils;

    private $upiInternalErrorCodesDict = null;

    private $pgCommonErrorCodesDict = null;

    public function __construct()
    {
        parent::__construct();

        $this->utils = new Utils();
    }

    public function getTurboErrorMappings(): array
    {
        try
        {
            return $this->fetchErrorMappingsAndErrorMappingsHashFromRedis();
        }
        catch (\Throwable $redisException)
        {
            $this->trace->traceException(
                $redisException,
                Logger::CRITICAL,
                TraceCode::UPI_TURBO_ERROR_MAPPINGS_REDIS_FETCH_FAILURE,
            );
        }

        // In case redis fetch fails, we will push a message to queue to update redis configs asynchronously.
        try
        {
            UpiTurboErrorMappingUpdater::dispatch($this->mode ?? Mode::LIVE);

            $this->trace->info(TraceCode::TURBO_ERROR_MAPPING_UPDATER_QUEUE_PUSH_SUCCESS);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::CRITICAL, TraceCode::TURBO_ERROR_MAPPING_UPDATER_QUEUE_PUSH_FAILURE);
        }

        throw $redisException;
    }

    public function generateTurboErrorMappings(array $gateways = []): array
    {
        //First we want to convert the upi error codes array into a dictionary format so that subsequent lookup is fast
        $this->upiInternalErrorCodesDict = $this->convertErrorCodesToDict(Constants::PG_UPI_ERROR_CODES_FILE_PATH);
        $this->pgCommonErrorCodesDict = $this->convertErrorCodesToDict(Constants::PG_COMMON_ERROR_CODES_FILE_PATH);

        $errorMappingsArray = $this->loadGatewayLevelErrorMappingForUpi($gateways);

        $this->loadCommonErrorMappings( $errorMappingsArray);

        $this->populateFallbackErrorMapping($errorMappingsArray);

        $this->trace->info(TraceCode::TURBO_UPI_ERROR_MAPPING_STEP_COMPLETE,
                           [
                               'step'       => 'generate_error_mapping_object',
                           ]);

        /*
         * At this point, the error mapping is generated in the format that is expected by the Turbo SDK.
         * Refer: https://docs.google.com/document/d/1sdoGpZL8ufLk5pRdWpvpXtnfoXMtwE9u2tJBcjmtoN8/edit#bookmark=id.hncit8wewt60
         * But, we will only set in Redis if the function is invoked as a part of the Admin dashboard call
         *
         * Now, we will do the following before returning the error mapping object
         *  1. Generate the hash of the file and store it in Redis
         *  2. Save the error object as a whole in Redis
         */

        $errorMappingHash = $this->setErrorMappingsAndErrorHashInRedis($errorMappingsArray);

        return [$errorMappingsArray, $errorMappingHash];
    }

    public function fetchErrorMappingsAndErrorMappingsHashFromRedis()
    {
        $errorMappingsHash = ConfigKey::get(ConfigKey::TURBO_SDK_ERROR_MAPPINGS_HASH, '');
        $errorMappingsObject = ConfigKey::get(ConfigKey::TURBO_SDK_ERROR_MAPPINGS, []);

        if (empty($errorMappingsObject) === true or empty($errorMappingsHash) === true)
        {
            throw new ServerErrorException(
                "Failed to fetch error mappings from Redis",
                ErrorCode::SERVER_ERROR,
                [
                    'error_hash_empty' => empty($errorMappingsHash),
                    'error_map_empty' => empty($errorMappingsObject)
                ]
            );
        }

        return [$errorMappingsObject, $errorMappingsHash];
    }

    public function setErrorMappingsAndErrorHashInRedis($errorMappingArray)
    {
        $errorMappingsHash = '';

        try
        {
            $errorMappingsString = json_encode($errorMappingArray);

            $errorMappingsHash = hash('sha256', $errorMappingsString);

            (new \RZP\Models\Admin\Service())->setConfigKeys(
                [
                    ConfigKey::TURBO_SDK_ERROR_MAPPINGS      => $errorMappingArray,
                    ConfigKey::TURBO_SDK_ERROR_MAPPINGS_HASH => $errorMappingsHash,
                ]
            );
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::TURBO_ERROR_MAPPING_STEP_ERROR,
                [
                    'step'               => 'redis_set',
                    'error_mapping_hash' => $errorMappingsHash,
                ]
            );
        }

        $this->trace->info(TraceCode::UPI_TURBO_ERROR_MAPPING_REDIS_SET_SUCCESS);

        return $errorMappingsHash;
    }

    public function loadGatewayLevelErrorMappingForUpi(array $gateways): array
    {
        $gateways = $this->getGatewaysForFetchingErrorMappings($gateways);

        $result = [];

        foreach ($gateways as $gateway)
        {
            $this->loadGatewaySpecificErrorMappings($gateway, $result);
        }

        $this->trace->info(TraceCode::TURBO_UPI_ERROR_MAPPING_STEP_COMPLETE,
                           [
                               'step' => 'load_gateway_error_mapping_total',
                           ]);

        return $result;
    }

    private function convertErrorCodesToDict($filePath): array
    {
        $step           = 'convert_to_dict';
        $errorCodesDict = [];

        try
        {
            $errorCodesArray = $this->utils->getFileContentsAsArray($filePath);

            foreach ($errorCodesArray as $errorObject)
            {
                $errorCodesDict[$errorObject[Constants::ERROR_CODE]] = $errorObject;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::TURBO_ERROR_MAPPING_STEP_ERROR,
                [
                    'step' => $step,
                ]
            );
        }

        $this->trace->info(TraceCode::TURBO_UPI_ERROR_MAPPING_STEP_COMPLETE,
                           [
                               'step' => $step,
                           ]);

        return $errorCodesDict;
    }

    private function loadGatewaySpecificErrorMappings($gateway, &$result)
    {
        $step = 'load_gateway_error_mapping';

        $gatewayErrorMappingFilePath = Constants::PG_UPI_GATEWAY_ERROR_MAPPING_DIR_PATH . $gateway . '.json';

        try
        {
            $gatewayErrorMappingArray = $this->utils->getFileContentsAsArray($gatewayErrorMappingFilePath);

            foreach ($gatewayErrorMappingArray as $gatewayErrorCode => $upiErrorCode)
            {
                $errorObject = $this->loadErrorObjectBasedOnErrorCodePrefix($upiErrorCode);

                if (empty($errorObject) === true)
                {
                    continue;
                }

                $result[Constants::GATEWAYS][$gateway][$gatewayErrorCode] =
                    array_intersect_key($errorObject, array_flip(Constants::TURBO_ERROR_CODE_FIELDS));
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::TURBO_ERROR_MAPPING_STEP_ERROR,
                [
                    'step' => $step,
                    Constants::GATEWAY => $gateway
                ]
            );
        }
    }

    private function loadCommonErrorMappings(&$result)
    {
        $result[Constants::COMMON] = [];

        try
        {
            $commonErrorMappingArray = $this->utils->getFileContentsAsArray(Constants::UPI_COMMON_GATEWAY_ERROR_MAPPING_FILE_PATH);

            foreach ($commonErrorMappingArray as $commonGatewayErrorCode => $upiErrorCode)
            {
                $errorObject = $this->loadErrorObjectBasedOnErrorCodePrefix($upiErrorCode);

                if (empty($errorObject) === true)
                {
                    continue;
                }

                $result[Constants::COMMON][$commonGatewayErrorCode] = array_intersect_key($errorObject, array_flip(Constants::TURBO_ERROR_CODE_FIELDS));
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Logger::CRITICAL,
                TraceCode::FAILED_TO_GENERATE_COMMON_ERROR_MAPPING
            );
        }

        $this->trace->info(TraceCode::TURBO_UPI_ERROR_MAPPING_STEP_COMPLETE,
                           [
                               'step' => 'load_common_error_mapping',
                           ]);
    }

    /* If $gateways = [], this method returns the list of all gateways for which there is an error mapping file present.
     * But, for our use case, we will always pass 'upi_axisolive' as the only value for the gateway.
     */
    private function getGatewaysForFetchingErrorMappings(array $gateways): array
    {
        if (empty($gateways) === false)
        {
            return $gateways;
        }

        $allGateways = [];

        $filePathPattern = base_path(Constants::PG_UPI_GATEWAY_ERROR_MAPPING_DIR_PATH) . '*.json';

        $gatewayErrorMappingsFilePaths = glob($filePathPattern);

        foreach ($gatewayErrorMappingsFilePaths as $filePath)
        {
            $pathExplodes = explode('/', $filePath);

            $gatewayName = substr($pathExplodes[count($pathExplodes)-1], 0, -5);

            $allGateways[] = $gatewayName;
        }

        return $allGateways;
    }

    private function populateFallbackErrorMapping(&$mappingArray)
    {
        $fallbackError[Error::DESCRIPTION]           = Constants::FALLBACK_ERROR_DESCRIPTION;
        $fallbackError[Constants::PUBLIC_ERROR_CODE] = Constants::FALLBACK_PUBLIC_ERROR_CODE;
        $fallbackError[Error::REASON]                = Constants::FALLBACK_REASON;

        $mappingArray[Constants::FALLBACK] = $fallbackError;
    }

    private function loadErrorObjectBasedOnErrorCodePrefix($upiErrorCode)
    {
        $errorCodePrefix = substr($upiErrorCode, 0, 4);
        $errorObject = [];

        switch ($errorCodePrefix)
        {
            case "PGUP":
                $errorObject = $this->upiInternalErrorCodesDict[$upiErrorCode];
                break;

            case "PGCM":
                $errorObject = $this->pgCommonErrorCodesDict[$upiErrorCode];
                break;
        }

        return $errorObject;
    }
}
