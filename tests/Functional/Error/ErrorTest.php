<?php


namespace Functional\Error;

use RZP\Error\Error;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Services\ErrorMappingService;

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

        $allErrorMapping = array();

        foreach (ErrorMappingService::EMM_NAMESPACES_PATH_VS_JSON_MAPPING as $namespacePath => $generatedJsonPath)
        {
            $filename = sprintf(ErrorMappingService::FETCHED_ERROR_CODES_PATH, $namespacePath);

            $namespaceArray = Error::readMappingFromJsonFile(base_path($filename), null, false);

            $allErrorMapping = array_merge($allErrorMapping, $namespaceArray);
        }

        $shouldNotBeInRepoError = ["SUCCESS", "INVALID_ARGUMENT_INVALID_FILE_HANDLER_SOURCE","UNHANDLED_KYC_PROCESSOR_TYPE",
            "INVALID_ARGUMENT_INVALID_INTERNATIONAL_ACTIVATION_FLOW",
            "SERVER_ERROR_LEDGER_JOURNAL_FETCH_TRANSACTION", "SERVER_ERROR_LEDGER_ACCOUNT_FETCH_BALANCES",
            "FRESHDESK_TICKET_ALREADY_EXISTS","FRESHDESK_TICKET_INVALID_ID","BAD_REQUEST_CREDIT_BAS_ID_MISSING",
            "BAD_REQUEST_MERCHANT_APP_MAPPING_DOES_NOT_EXIST",
            "BAD_REQUEST_MERCHANT_NOTIFICATION_CONFIG_INVALID_MOBILE_NUMBER",
            "BAD_REQUEST_APP_ID_INVALID","BAD_REQUEST_DUPLICATE_TAG","BAD_REQUEST_APP_DOES_NOT_EXIST",
            "BAD_REQUEST_DUPLICATE_MERCHANT_TAG","BAD_REQUEST_MERCHANT_TAG_DOES_NOT_EXIST",
            "BAD_REQUEST_APP_ALREADY_EXIST","BAD_REQUEST_MERCHANT_TAG_IN_USE","BAD_REQUEST_APP_ALREADY_IN_USE",
            "BAD_REQUEST_APP_TAG_MAPPING_DOES_NOT_EXIST","BAD_REQUEST_EMPTY_DELETE_LIST","BAD_REQUEST_SERVER_ERROR_FILE_FETCH_FAILURE",
            "BAD_REQUEST_WALLET_ACCOUNT_FUND_ACCOUNT_CREATION_NOT_PERMITTED","SERVER_ERROR_IN_RECON_RESPONSE",
            "BAD_REQUEST_ERROR_IN_RECON_RESPONSE","SERVER_ERROR_RECON_REQUEST_FAILURE","BAD_REQUEST_PAYOUTS_BATCH_NOT_ALLOWED",
            "BAD_REQUEST_ERROR_IN_RECON_RESPONSE","SERVER_ERROR_RECON_REQUEST_FAILURE",
            "BAD_REQUEST_ACTION_RISK_ATTRIBUTES_REQUIRED","BAD_REQUEST_INVALID_ACTION_RISK_REASON",
            "BAD_REQUEST_INVALID_ACTION_RISK_SOURCE","BAD_REQUEST_INVALID_ACTION_RISK_TAG", "LEDGER_MERCHANT_BALANCE_GET_ERROR",
            "BAD_REQUEST_LEDGER_JOURNAL_ENTRY_BALANCE_GET_ERROR", "BAD_REQUEST_INVALID_ACTION_CLEAR_TAG_VALUE",
            "BAD_REQUEST_UNSUPPORTED_COMMUNICATION_TYPE", "BAD_REQUEST_CORPORATE_CARD_INVALID_TOKEN",
        ];

        $errorCodeBatches = array();

        $count = 0;

        $errorBatchCount = 0;

        foreach($allErrorMapping as $key => $value)
        {
            if (($count % 400) === 0)
            {
                $errorBatchCount += 1;
            }

            $errorCodeBatches[$errorBatchCount][$value['internal_error_code']] = true;

            ++$count;
        }

        foreach ($finalDefinedErrorCodes as $key => $value)
        {
            if (in_array($key, $shouldNotBeInRepoError) === true)
            {
                continue;
            }

            $foundErrorCode = false;
            foreach ($errorCodeBatches as $batchNo => $batch)
            {
                if (array_key_exists($key, $batch))
                {
                    $foundErrorCode = true;
                }
            }
            if ($foundErrorCode === false)
            {
                self::fail("Internal Error Code ".$key." defined in ErrorCode class but mapping not available in Common Error Repo");
            }
        }
    }
}
