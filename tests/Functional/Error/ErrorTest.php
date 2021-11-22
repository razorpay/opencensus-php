<?php


namespace Functional\Error;


use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Services\ErrorMappingService;
use RZP\Tests\Functional\TestCase;

class ErrorTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testErrorMappingAvalaibleForInternalErrorCode()
    {
        $definedErrors = ErrorCode::getConstants();

        $definedP2PErrors = \RZP\Error\P2p\ErrorCode::getConstants();

        $definedTerminalOnboardingErrors = \RZP\Error\TerminalOnboarding\ErrorCode::getConstants();

        $finalDefinedErrorCodes = array_unique(array_merge($definedErrors, $definedP2PErrors, $definedTerminalOnboardingErrors));

        unset($definedErrors, $definedP2PErrors, $definedTerminalOnboardingErrors);

        $allErrorMapping = array_merge(Error::readMappingFromJsonFile(base_path(ErrorMappingService::APP_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::CARD_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::CARDLESS_EMI_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::COMMON_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::EMANDATE_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::NACH_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::NETBANKING_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::UPI_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::WALLET_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::COD_ERROR_CODES_JSON), null, false),
            Error::readMappingFromJsonFile(base_path(ErrorMappingService::PAYLATER_ERROR_CODES_JSON), null, false));

        $shouldNotBeInRepoError = ["SUCCESS", "INVALID_ARGUMENT_INVALID_FILE_HANDLER_SOURCE","UNHANDLED_KYC_PROCESSOR_TYPE",
            "INVALID_ARGUMENT_INVALID_INTERNATIONAL_ACTIVATION_FLOW",
            "FRESHDESK_TICKET_ALREADY_EXISTS","FRESHDESK_TICKET_INVALID_ID","BAD_REQUEST_CREDIT_BAS_ID_MISSING",
            "BAD_REQUEST_MERCHANT_APP_MAPPING_DOES_NOT_EXIST",
            "BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MOBILE_NUMBER",
            "BAD_REQUEST_APP_ID_INVALID","BAD_REQUEST_DUPLICATE_TAG","BAD_REQUEST_APP_DOES_NOT_EXIST",
            "BAD_REQUEST_DUPLICATE_MERCHANT_TAG","BAD_REQUEST_MERCHANT_TAG_DOES_NOT_EXIST",
            "BAD_REQUEST_APP_ALREADY_EXIST","BAD_REQUEST_MERCHANT_TAG_IN_USE","BAD_REQUEST_APP_ALREADY_IN_USE",
            "BAD_REQUEST_APP_TAG_MAPPING_DOES_NOT_EXIST","BAD_REQUEST_EMPTY_DELETE_LIST","BAD_REQUEST_SERVER_ERROR_FILE_FETCH_FAILURE",
            "BAD_REQUEST_WALLET_ACCOUNT_FUND_ACCOUNT_CREATION_NOT_PERMITTED","SERVER_ERROR_IN_RECON_RESPONSE",
            "BAD_REQUEST_ERROR_IN_RECON_RESPONSE","SERVER_ERROR_RECON_REQUEST_FAILURE",
            "BAD_REQUEST_ACTION_RISK_ATTRIBUTES_REQUIRED","BAD_REQUEST_INVALID_ACTION_RISK_REASON",
            "BAD_REQUEST_INVALID_ACTION_RISK_SOURCE","BAD_REQUEST_INVALID_ACTION_RISK_TAG",
            "BAD_REQUEST_INVALID_ACTION_CLEAR_TAG_VALUE","BAD_REQUEST_UNSUPPORTED_COMMUNICATION_TYPE",
        ];

        $allErrorCodes1 = array();
        $allErrorCodes2 = array();
        $allErrorCodes3 = array();
        $allErrorCodes4 = array();
        $allErrorCodes5 = array();
        $allErrorCodes6 = array();
        $allErrorCodes7 = array();
        $allErrorCodes8 = array();
        $allErrorCodes9 = array();
        $count = 0;
        
       foreach($allErrorMapping as $key => $value)
       {
           if ($count >= 0 and $count <= 400)
           {
               $allErrorCodes1[$value['internal_error_code']] = true;
           }
           if ($count >= 401 and $count <= 800)
           {
               $allErrorCodes2[$value['internal_error_code']] = true;
           }
           if ($count >= 801 and $count <= 1200)
           {
               $allErrorCodes3[$value['internal_error_code']] = true;
           }
           if ($count >= 1201 and $count <= 1600)
           {
               $allErrorCodes4[$value['internal_error_code']] = true;
           }
           if ($count >= 1601 and $count <= 2000)
           {
               $allErrorCodes5[$value['internal_error_code']] = true;
           }
           if ($count >= 2001 and $count <= 2400)
           {
               $allErrorCodes6[$value['internal_error_code']] = true;
           }
           if ($count >= 2401 and $count <= 2800)
           {
               $allErrorCodes7[$value['internal_error_code']] = true;
           }
           if ($count >= 2801 and $count <= 3200)
           {
               $allErrorCodes8[$value['internal_error_code']] = true;
           }
           if ($count >= 3201 and $count <= 3600)
           {
               $allErrorCodes9[$value['internal_error_code']] = true;
           }

            ++$count;
       }

       foreach ($finalDefinedErrorCodes as $key => $value)
       {
           if (in_array($key, $shouldNotBeInRepoError) === true)
           {
                   continue;
           }

           $foundErrorCode = false;

           if ((array_key_exists($key, $allErrorCodes1) === true) or
               (array_key_exists($key, $allErrorCodes2) === true) or
               (array_key_exists($key, $allErrorCodes3) === true) or
               (array_key_exists($key, $allErrorCodes4) === true) or
               (array_key_exists($key, $allErrorCodes5) === true) or
               (array_key_exists($key, $allErrorCodes6) === true) or
               (array_key_exists($key, $allErrorCodes7) === true) or
               (array_key_exists($key, $allErrorCodes8) === true) or
               (array_key_exists($key, $allErrorCodes9) === true))
           {
               $foundErrorCode = true;
           }

           if ($foundErrorCode === false)
           {
              self::fail("Internal Error Code ".$key." defined in ErrorCode class but mapping not available in Common Error Repo");
           }
       }
    }
}
