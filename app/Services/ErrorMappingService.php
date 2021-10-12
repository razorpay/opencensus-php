<?php


namespace RZP\Services;

use JsonMachine\JsonMachine;
use RZP\Models\Payment\Method;

class ErrorMappingService
{

    protected $trace;

    public const APP_ERROR_CODES_JSON               = 'error_codes/error_codes/pg/app/internal_error_codes.json';

    public const CARD_ERROR_CODES_JSON              = 'error_codes/error_codes/pg/card/internal_error_codes.json';

    public const CARDLESS_EMI_ERROR_CODES_JSON      = 'error_codes/error_codes/pg/cardless_emi/internal_error_codes.json';

    public const COMMON_ERROR_CODES_JSON            = 'error_codes/error_codes/pg/common/internal_error_codes.json';

    public const EMANDATE_ERROR_CODES_JSON          = 'error_codes/error_codes/pg/emandate/internal_error_codes.json';

    public const NACH_ERROR_CODES_JSON              = 'error_codes/error_codes/pg/nach/internal_error_codes.json';

    public const NETBANKING_ERROR_CODES_JSON        = 'error_codes/error_codes/pg/netbanking/internal_error_codes.json';

    public const UPI_ERROR_CODES_JSON               = 'error_codes/error_codes/pg/upi/internal_error_codes.json';

    public const WALLET_ERROR_CODES_JSON            = 'error_codes/error_codes/pg/wallet/internal_error_codes.json';

    public const COD_ERROR_CODES_JSON               = 'error_codes/error_codes/pg/cod/internal_error_codes.json';

    public const APP_ERROR_CODES_JSON_FILE          = 'files/errorcodes/app_error_codes.json';

    public const CARD_ERROR_CODES_JSON_FILE         = 'files/errorcodes/card_error_codes.json';

    public const CARDLESS_EMI_ERROR_CODES_JSON_FILE = 'files/errorcodes/cardless_emi_error_codes.json';

    public const COMMON_ERROR_CODES_JSON_FILE       = 'files/errorcodes/common_error_codes.json';

    public const EMANDATE_ERROR_CODES_JSON_FILE     = 'files/errorcodes/emandate_error_codes.json';

    public const NACH_ERROR_CODES_JSON_FILE         = 'files/errorcodes/nach_error_codes.json';

    public const NETBANKING_ERROR_CODES_JSON_FILE   = 'files/errorcodes/netbanking_error_codes.json';

    public const UPI_ERROR_CODES_JSON_FILE          = 'files/errorcodes/upi_error_codes.json';

    public const WALLET_ERROR_CODES_JSON_FILE       = 'files/errorcodes/wallet_error_codes.json';

    public const COD_ERROR_CODES_JSON_FILE          = 'files/errorcodes/cod_error_codes.json';


    protected $app;

    public function __construct($app)
    {
        $this->app = $app;

        $this->trace = $app['trace'];

        $this->loadErrorMapping();
    }

    protected function loadErrorMapping()
    {
       if (file_exists(storage_path(self::APP_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::CARD_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::CARDLESS_EMI_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::COMMON_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::EMANDATE_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::NACH_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::NETBANKING_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::UPI_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::WALLET_ERROR_CODES_JSON_FILE)) === false or
           file_exists(storage_path(self::COD_ERROR_CODES_JSON_FILE)) === false)
       {
           $this->readMappingFromFiles();
       }
    }

    public function getErrorMapping($code, $method)
    {
        $array = array();

        if ($method === Method::APP)
        {
            $array =  json_decode(file_get_contents(storage_path(self::APP_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::CARD)
        {
            $array =  json_decode(file_get_contents(storage_path(self::CARD_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::CARDLESS_EMI)
        {
            $array =  json_decode(file_get_contents(storage_path(self::CARDLESS_EMI_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::EMANDATE)
        {
            $array =  json_decode(file_get_contents(storage_path(self::EMANDATE_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::NACH)
        {
            $array =  json_decode(file_get_contents(storage_path(self::NACH_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::NETBANKING)
        {
            $array =  json_decode(file_get_contents(storage_path(self::NETBANKING_ERROR_CODES_JSON_FILE)), true);
        }

        if ($method === Method::UPI)
        {
            $array =  json_decode(file_get_contents(storage_path(self::UPI_ERROR_CODES_JSON_FILE)), true);
        }
        if ($method === Method::WALLET)
        {
            $array =  json_decode(file_get_contents(storage_path(self::WALLET_ERROR_CODES_JSON_FILE)), true);
        }
        if ($method === Method::COD)
        {
            $array =  json_decode(file_get_contents(storage_path(self::COD_ERROR_CODES_JSON_FILE)), true);
        }

        if (isset($array[$code]) === false)
        {
            $array =  json_decode(file_get_contents(storage_path(self::COMMON_ERROR_CODES_JSON_FILE)), true);

            return isset($array[$code]) === true ? array(json_decode($array[$code], true), 'common') : array(null, '');
        }
        else
        {
            return array(json_decode($array[$code], true), $method);
        }
    }

    protected function readMappingFromFiles()
    {
        $appErrorCodeArray = JsonMachine::fromFile(base_path(self::APP_ERROR_CODES_JSON));

        $cardErrorCodeArray = JsonMachine::fromFile(base_path(self::CARD_ERROR_CODES_JSON));

        $cardlessEmiErrorCodeArray = JsonMachine::fromFile(base_path(self::CARDLESS_EMI_ERROR_CODES_JSON));

        $commonErrorCodeArray = JsonMachine::fromFile(base_path(self::COMMON_ERROR_CODES_JSON));

        $emandateErrorCodeArray = JsonMachine::fromFile(base_path(self::EMANDATE_ERROR_CODES_JSON));

        $nachErrorCodeArray = JsonMachine::fromFile(base_path(self::NACH_ERROR_CODES_JSON));

        $upiErrorCodeArray = JsonMachine::fromFile(base_path(self::UPI_ERROR_CODES_JSON));

        $netbankingErrorCodeArray = JsonMachine::fromFile(base_path(self::NETBANKING_ERROR_CODES_JSON));

        $walletErrorCodeArray = JsonMachine::fromFile(base_path(self::WALLET_ERROR_CODES_JSON));

        $codErrorCodeArray = JsonMachine::fromFile(base_path(self::COD_ERROR_CODES_JSON));

        $appErrorsJson = array();

        $cardErrorsJson = array();

        $cardlessEmiErrorsJson = array();

        $commonErrorsJson = array();

        $emandateErrorsJson = array();

        $nachErrorsJson = array();

        $upiErrorsJson = array();

        $netbankingErrorsJson = array();

        $walletErrorsJson = array();

        $codErrorsJson = array();

        foreach($appErrorCodeArray as $key => $value)
        {
            $appErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($cardErrorCodeArray as $key => $value)
        {
            $cardErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($cardlessEmiErrorCodeArray as $key => $value)
        {
            $cardlessEmiErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($commonErrorCodeArray as $key => $value)
        {
            $commonErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($emandateErrorCodeArray as $key => $value)
        {
            $emandateErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($nachErrorCodeArray as $key => $value)
        {
            $nachErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($upiErrorCodeArray as $key => $value)
        {
            $upiErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($netbankingErrorCodeArray as $key => $value)
        {
            $netbankingErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($walletErrorCodeArray as $key => $value)
        {
            $walletErrorsJson[$value['internal_error_code']] = json_encode($value);
        }
        foreach($codErrorCodeArray as $key => $value)
        {
            $codErrorsJson[$value['internal_error_code']] = json_encode($value);
        }

        file_put_contents(storage_path(self::APP_ERROR_CODES_JSON_FILE), json_encode($appErrorsJson));

        file_put_contents(storage_path(self::CARD_ERROR_CODES_JSON_FILE), json_encode($cardErrorsJson));

        file_put_contents(storage_path(self::CARDLESS_EMI_ERROR_CODES_JSON_FILE), json_encode($cardlessEmiErrorsJson));

        file_put_contents(storage_path(self::COMMON_ERROR_CODES_JSON_FILE), json_encode($commonErrorsJson));

        file_put_contents(storage_path(self::EMANDATE_ERROR_CODES_JSON_FILE), json_encode($emandateErrorsJson));

        file_put_contents(storage_path(self::NACH_ERROR_CODES_JSON_FILE), json_encode($nachErrorsJson));

        file_put_contents(storage_path(self::UPI_ERROR_CODES_JSON_FILE), json_encode($upiErrorsJson));

        file_put_contents(storage_path(self::NETBANKING_ERROR_CODES_JSON_FILE), json_encode($netbankingErrorsJson));

        file_put_contents(storage_path(self::WALLET_ERROR_CODES_JSON_FILE), json_encode($walletErrorsJson));

        file_put_contents(storage_path(self::COD_ERROR_CODES_JSON_FILE), json_encode($codErrorsJson));
    }
}
