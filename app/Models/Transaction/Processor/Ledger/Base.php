<?php

namespace RZP\Models\Transaction\Processor\Ledger;

use App;
use Ramsey\Uuid\Uuid;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Base\Core;
use RZP\Exception\BadRequestException;
use RZP\Services\Ledger as LedgerService;
use RZP\Exception\GatewayTimeoutException;
use RZP\Exception\BadRequestValidationFailureException;

class Base extends Core
{
    const TENANT                = 'tenant';
    const MODE                  = 'mode';
    const MERCHANT_ID           = 'merchant_id';
    const BALANCE_ID            = 'balance_id';
    const CURRENCY              = 'currency';
    const AMOUNT                = 'amount';
    const BASE_AMOUNT           = 'base_amount';
    const COMMISSION            = 'commission';
    const TAX                   = 'tax';
    const NOTES                 = 'notes';
    const FTS_FUND_ACCOUNT_ID   = 'fts_fund_account_id';
    const FTS_ACCOUNT_TYPE      = 'fts_account_type';
    const TERMINAL_ID           = 'terminal_id';
    const TERMINAL_ACCOUNT_TYPE = 'terminal_account_type';
    const TRANSACTION_ID        = 'transaction_id';
    const TRANSACTION_DATE      = 'transaction_date';
    const TRANSACTOR_ID         = 'transactor_id';
    const TRANSACTOR_EVENT      = 'transactor_event';
    const TRANSACTION_CONFIG_ID = 'transaction_config_id';
    const ENTITY                = 'entity';
    const IDEMPOTENCY_KEY       = 'idempotency_key';
    const BANKING_ACCOUNT_ID    = 'banking_account_id';
    const API_TRANSACTION_ID    = 'api_transaction_id';
    const IDENTIFIERS           = 'identifiers';
    const ADDITIONAL_PARAMS     = 'additional_params';

    // For txn sqs
    const ENTITY_ID                 = 'entity_id';
    const ENTITY_NAME               = 'entity_name';
    const LEDGER_RESPONSE           = 'ledger_response';

    // For Fee Credit accounting
    const FEE_ACCOUNTING        = 'fee_accounting';
    const REWARD                = 'reward';

    const UUID_FORMAT = '%04x%04x-%04x-%04x-%04x-%04x%04x%04x';

    const TIME_TAKEN = 'time_taken';

    const DEFAULT_EVENT                            = "default_event";
    const DEFAULT_FTS_FUND_ACCOUNT_ID              = '100000000';
    const DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_ID   = '100000001';
    const DEFAULT_M2P_FTS_FUND_ACCOUNT_ID          = '100000002';
    const DEFAULT_FTS_FUND_ACCOUNT_TYPE            = 'nodal';
    const DEFAULT_AMAZON_PAY_FTS_FUND_ACCOUNT_TYPE = 'amazonpay';
    const DEFAULT_M2P_FTS_FUND_ACCOUNT_TYPE        = 'm2p';
    const DEFAULT_TERMINAL_ACCOUNT_TYPE            = 'nodal';

    const X = 'X';

    const LEDGER_TRANSACTION_CREATE = 'ledger_transaction_create';

    // Constants for reading ledger response
    const LEDGER_ENTRY      = 'ledger_entry';
    const ACCOUNT_ENTITIES  = 'account_entities';
    const ACCOUNT_TYPE      = 'account_type';
    const FUND_ACCOUNT_TYPE = 'fund_account_type';
    const PAYABLE           = 'payable';
    const MERCHANT_VA       = 'merchant_va';
    const BALANCE           = 'balance';

    // Ledger retry
    const DEFAULT_MAX_RETRY_COUNT = 3;

    public static function getMerchantBalanceFromLedgerResponse(array $ledgerResponse)
    {
        foreach($ledgerResponse[self::LEDGER_ENTRY] as $ledgerEntry)
        {
            if ((empty($ledgerEntry[self::ACCOUNT_ENTITIES][self::ACCOUNT_TYPE]) === false) and
                (empty($ledgerEntry[self::ACCOUNT_ENTITIES][self::FUND_ACCOUNT_TYPE]) === false) and
                ($ledgerEntry[self::ACCOUNT_ENTITIES][self::ACCOUNT_TYPE][0] === self::PAYABLE) and
                ($ledgerEntry[self::ACCOUNT_ENTITIES][self::FUND_ACCOUNT_TYPE][0] === self::MERCHANT_VA))
            {
                return $ledgerEntry[self::BALANCE];
            }
        }

        // throw error if reaches here
        throw new BadRequestValidationFailureException(
            Errorcode::BAD_REQUEST_LEDGER_JOURNAL_ENTRY_BALANCE_GET_ERROR,
            null,
            $ledgerResponse
        );
    }

    /**
     * @param array $payload
     * This function pushes the payload to sns which will be used by ledger. This is
     * being done only for shadow mode and will not depend on ledger's response.
     * Later on, API will directly interact with ledger to create transactions instead of
     * this sns flow, and would use the ledger's response.
     */
    protected function pushToLedgerSns(array $payload)
    {
        $this->trace->info(TraceCode::LEDGER_JOURNAL_STREAMING_STARTED, $payload);

        try
        {
            $sns = $this->app['sns'];

            $target = self::LEDGER_TRANSACTION_CREATE;

            $sns->publish(json_encode($payload), $target);

        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_JOURNAL_STREAMING_FAILED,
                $payload);
        }
    }

    /**
     * This function is used to call the Ledger service for any transactor event.
     * This call shall tell ledger to create a new journal entry, ledger entry and adjust the balance/Chart of Accounts
     * Please extend this function in child classes if exceptions are to be handled in a custom way.
     *
     * @param array $payload
     *
     * @throws \RZP\Exception\RuntimeException
     * @throws \RZP\Exception\BadRequestException
     * @throws \RZP\Exception\BadRequestValidationFailureException
     * @throws \RZP\Exception\GatewayTimeoutException
     * @throws \Throwable
     */
    public function createJournalEntry(array $payload, int $maxRetryCount = self::DEFAULT_MAX_RETRY_COUNT, int $retryCount = 0)
    {
        $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST, $payload);
        try
        {
            $ledgerService = $this->app['ledger'];
            // use same idempotency key for retry
            if ($retryCount === 0)
            {
                $ledgerService->setIdempotencyKey(Uuid::uuid1());
            }
            $response = $ledgerService->createJournal($payload, true);
        }
        catch (\Requests_Exception $re)
        {
            // This is an ambiguous situation, retry the request
            // TODO: An alert here is absolutely essential
            $this->trace->traceException($re, Trace::CRITICAL, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_TIMEOUT,
            [
                'retries' => $retryCount
            ]);

            if ($retryCount < $maxRetryCount)
            {
                $retryCount++;
                return $this->createJournalEntry($payload, $maxRetryCount, $retryCount);
            } else {
                throw new GatewayTimeoutException($re->getMessage(), $re);
            }
        }
        catch (\RZP\Exception\RuntimeException $e)
        {
            $exceptionData = $e->getData();

            // If it's an insufficient balance case, convert to a new BadRequestException
            if (strpos($exceptionData['response_body']['msg'], ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE) !== false)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR);

                throw new BadRequestException(
                    Errorcode::BAD_REQUEST_INSUFFICIENT_BALANCE,
                    null,
                    $exceptionData
                );
            }
            // If it's a validation failure, convert to a new BadRequestValidationFailureException
            else if (strpos($exceptionData['response_body']['msg'], ErrorCode::BAD_REQUEST_VALIDATION_FAILURE) !== false)
            {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR);

                throw new BadRequestValidationFailureException(
                    Errorcode::BAD_REQUEST_VALIDATION_FAILURE,
                    null,
                    $exceptionData
                );
            }
            else
            {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST_ERROR,
                [
                    'retries' => $retryCount
                ]);
                // retry in sync for 5xx errors
                if (($exceptionData['status_code'] >= 500) && ($retryCount < $maxRetryCount))
                {
                    $retryCount++;
                    return $this->createJournalEntry($payload, $maxRetryCount, $retryCount);
                } else {
                    throw $e;
                }
            }
        }

        $this->trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE, $response);

        return $response;
    }

    /**
     * Create payload for Journal function for fundloading
     *
     * @param string $entityId
     *
     */
    public function createPayloadForTransactionEntry(string $entityId, string $entityName, array $ledgerResponse)
    {
        return [$entityId, $entityName, $ledgerResponse];
    }

    /***
     * @param string $event
     *
     * @return bool
     */
    protected function isDefaultEvent(string $event)
    {
        return ($event === self::DEFAULT_EVENT);
    }

}
