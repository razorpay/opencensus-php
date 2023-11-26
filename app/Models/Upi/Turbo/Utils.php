<?php

namespace RZP\Models\Upi\Turbo;

use App;
use RZP\Error\Error;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Entity;

class Utils extends \RZP\Models\Base\Core
{
    public static function getFileContentsAsArray($filePath)
    {
        $baseFilePath = base_path($filePath);

        $fileContents = file_get_contents($baseFilePath);

        $contentsArray = json_decode($fileContents, true);

        if (json_last_error() !== JSON_ERROR_NONE)
        {
            $app = App::getFacadeRoot();

            $app['trace']->error(
                TraceCode::ERROR_MAPPING_JSON_DECODE_FAILED,
                [
                    'base_file_path' => $baseFilePath,
                    'json_last_error' => json_last_error(),
                ]
            );
            return [];
        }

        return $contentsArray;
    }

    /**
     * 1. Fetch contents of gateway <> internal_error_code mapping file.
     * 2. Fetch appropriate internal_error_codes.json file based on errorCodePrefix
     * 3. Parse internal_error_codes.json and extract errorDetails based on errorCode and return
     * @param $gatewayStatusCode
     * @param $gateway
     *
     * @return array
     */
    public static function getErrorDetailsFromGatewayStatusCode($gateway, $gatewayStatusCode): array
    {
        $errorObjects = [];

        $filePath = Constants::PG_UPI_GATEWAY_ERROR_MAPPING_DIR_PATH . $gateway . ".json";
        $fileContentArray = self::getFileContentsAsArray($filePath);

        $errorCode       = $fileContentArray[$gatewayStatusCode];
        $errorCodePrefix = substr($errorCode, 0, 4);

        switch ($errorCodePrefix)
        {
            case "PGUP":
                //fetch error object from upi error codes.
                $errorObjects = self::getFileContentsAsArray(Constants::PG_UPI_ERROR_CODES_FILE_PATH);
                break;
            case "PGCM":
                //fetch error object from common error codes.
                $errorObjects = self::getFileContentsAsArray(Constants::PG_COMMON_ERROR_CODES_FILE_PATH);
                break;
        }

        $errorDetails = [];

        foreach ($errorObjects as $errorObj)
        {
            if($errorObj[Error::ERROR_CODE] === $errorCode)
            {
                $errorDetails[Entity::ERROR_REASON]         = $errorObj[Error::REASON];
                $errorDetails[Error::ERROR_DESCRIPTION]     = $errorObj[Error::ERROR_DESCRIPTION];
                $errorDetails[Constants::PUBLIC_ERROR_CODE] = $errorObj[Constants::PUBLIC_ERROR_CODE];
                $errorDetails[Error::INTERNAL_ERROR_CODE]   = $errorObj[Error::INTERNAL_ERROR_CODE];
                $errorDetails[Entity::ERROR_SOURCE]         = $errorObj[Error::SOURCE];
                $errorDetails[Entity::ERROR_STEP]           = $errorObj[Error::STEP];

                break;
            }
        }

        return $errorDetails;
    }
}
