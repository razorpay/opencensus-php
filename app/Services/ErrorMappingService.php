<?php


namespace RZP\Services;

use JsonMachine\JsonMachine;

class ErrorMappingService
{
    public const EMM_NAMESPACES_PATH_VS_JSON_MAPPING = [
        "pg/app"                                                => "app",
        "pg/card"                                               => "card",
        "pg/cardless_emi"                                       => "cardless_emi",
        "pg/common"                                             => "common",
        "pg/nach"                                               => "nach",
        "pg/netbanking"                                         => "netbanking",
        "pg/wallet"                                             => "wallet",
        "pg/cod"                                                => "cod",
        "pg/paylater"                                           => "paylater",
        "pg/upi"                                                => "upi",
        "pg/emandate"                                           => "emandate",
        "pg/pg-router"                                          => "pg_router",
        "x/payout_links"                                        => "x_payout_links",
        "pg/emi"                                                => "emi",
        "pg/upi_autopay"                                        => "upi_autopay",
        "pg/fpx"                                                => "fpx",
        ];

    public const FETCHED_ERROR_CODES_PATH = 'error_codes/error_codes/%s/internal_error_codes.json';

    public const KEY_VALUE_ERROR_CODES_PATH = 'files/errorcodes/%s_error_codes.json';

    protected $trace;

    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->loadErrorMapping();
    }

    protected function loadErrorMapping()
    {
        foreach (self::EMM_NAMESPACES_PATH_VS_JSON_MAPPING as $namespacePath => $generatedJsonPath)
        {
            $filename = sprintf(self::KEY_VALUE_ERROR_CODES_PATH,$generatedJsonPath);

            $isFileExists = file_exists(storage_path($filename));

            if ($isFileExists === false)
            {
                $this->readMappingFromFiles();

                break;
            }
        }
    }

    public function getErrorMapping($code, $method)
    {
        $errorMappingArray = array();

        if ((isset($method) === true) and
            in_array($method, array_values(self::EMM_NAMESPACES_PATH_VS_JSON_MAPPING)) !== false)
        {
            $namespaceFilePath = sprintf(self::KEY_VALUE_ERROR_CODES_PATH,$method);

            $errorMappingArray = json_decode(file_get_contents(storage_path($namespaceFilePath)), true);
        }

        if (isset($errorMappingArray[$code]) === true)
        {
            return array(json_decode($errorMappingArray[$code], true), $method);
        }

        if ((isset($method)) and ($method === 'upi_autopay') and isset($errorMappingArray[$code]) === false)
        {
            $upiNamespaceFilePath = sprintf(self::KEY_VALUE_ERROR_CODES_PATH,"upi");
            $errorMappingArrayFromUpi =  json_decode(file_get_contents(storage_path($upiNamespaceFilePath)), true);

            if (isset($errorMappingArrayFromUpi[$code]) === true)
            {
                return array(json_decode($errorMappingArrayFromUpi[$code], true), $method);
            }
        }

        $commonNamespaceFilePath = sprintf(self::KEY_VALUE_ERROR_CODES_PATH,"common");

        $errorMappingArrayFromCommon =  json_decode(file_get_contents(storage_path($commonNamespaceFilePath)), true);

        return isset($errorMappingArrayFromCommon[$code]) === true ? array(json_decode($errorMappingArrayFromCommon[$code], true), 'common') : array(null, '');
    }

    protected function readMappingFromFiles()
    {
        foreach (self::EMM_NAMESPACES_PATH_VS_JSON_MAPPING as $namespacePath => $generatedJsonPath)
        {
            $errorCodeFilePath = sprintf(self::FETCHED_ERROR_CODES_PATH, $namespacePath);

            $namespaceErrorCodeArray = JsonMachine::fromFile(base_path($errorCodeFilePath));

            $namespaceErrorsJson = array();

            foreach ($namespaceErrorCodeArray as $key => $value)
            {
                $namespaceErrorsJson[$value['internal_error_code']] = json_encode($value);
            }

            $errorJsonFilePath = sprintf(self::KEY_VALUE_ERROR_CODES_PATH, $generatedJsonPath);

            file_put_contents(storage_path($errorJsonFilePath), json_encode($namespaceErrorsJson));
        }
    }
}
