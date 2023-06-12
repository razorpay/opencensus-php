<?php

namespace RZP\Models\Ledger\ReverseShadow;

use App;
use Exception;
use Ramsey\Uuid\Uuid;
use RZP\Constants\Metric;
use RZP\Error\Error;
use RZP\Models\LedgerOutbox\Entity as LedgerOutboxEntity;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Ledger\Constants;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\Merchant;
use RZP\Models\Base\Entity;
use RZP\Models\Pricing\Fee;
use RZP\Services\Ledger as LedgerService;
use RZP\Trace\TraceCode;
use RZP\Models\Feature;
use RZP\Models\Payment\Processor as PaymentProcessor;

trait ReverseShadowTrait
{
    protected function generateBaseForJournalEntry(Entity  $entity, $transactionDate = null): array
    {
        if (isset($transactionDate) === false)
        {
            $transactionDate = $entity->getUpdatedAt();
        }
        return array(
            Constants::MERCHANT_ID               => $entity->getMerchantId(),
            Constants::CURRENCY                  => $entity->getCurrency(),
            Constants::TRANSACTION_DATE          => $transactionDate,
        );
    }

    // For payment in case of post-paid and dynamic fee bearer, where customer fee is not settled
    // We have to make sure that customer fee and customer fee gst is removed from merchant balance and
    // merchant receivable amount, hence this function.
    protected function getCustomerFeeAndCustomerFeeGst($payment,$fee,$tax)
    {
        if( $payment->hasOrder() === true and
            $payment->order->getFeeConfigId() !== null )
        {
            $order = $this->repo->order->findByPublicId($payment->getOrderId());

            $customerFee = (new PaymentProcessor\processor($payment->merchant))->calculateCustomerFee($payment, $order, $fee);

            $customerFeeTax = (new PaymentProcessor\processor($payment->merchant))->calculateCustomerFeeGst($customerFee, $fee, $tax);

            return [$customerFee, $customerFeeTax];
        }

        return [0,0];
    }

    protected function isFeeCreditsWithoutCustomerFeeBearer($feeCredits ,$fee, PaymentEntity $payment)
    {
        return (($feeCredits > 0) and ($feeCredits >= $fee) and ($payment->isFeeBearerCustomer() === false));
    }

    protected function isPostPaidDynamicFeeBearerFlag(PaymentEntity $payment,$merchant)
    {
        return ($this->isPostpaid($payment) === true and ($merchant->isFeeBearerDynamic() === true)
                and $merchant->isFeatureEnabled(Feature\Constants::CUSTOMER_FEE_DONT_SETTLE) === true);
    }

    protected function isFeeCredits($feeCredits ,$fee): bool
    {
        return (($feeCredits > 0) and ($feeCredits >= $fee));
    }

    protected function isGratisWithoutCustomerFeeBearer($amountCredits ,$amount, PaymentEntity $payment)
    {
        return (($amountCredits > 0) and ($amount !== 0) and ($payment->isFeeBearerCustomer() === false));
    }

    protected function isGratis($amountCredits ,$amount): bool
    {
        return (($amountCredits > 0) and ($amount !== 0));
    }

    protected function isRefundCredits($merchant): bool
    {
        return ($merchant->getRefundSource() === RefundSource::CREDITS);
    }

    protected function isPostPaidWithoutCustomerFeeBearer(PaymentEntity $payment)
    {
        return (($this->isPostpaid($payment) === true) and ($payment->isFeeBearerCustomer() === false));
    }

    protected function isPostpaid(PaymentEntity $payment): bool
    {
        return ($payment->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID);
    }

    protected function getPayloadName($transactorId, $transactorEvent): string
    {
        return sprintf("%s-%s", $transactorId, $transactorEvent);
    }

    protected function getMerchantAccountBalances($ledgerService, $merchantId): array
    {
        $accountPayload = $this->getAccountBalancePayload($merchantId);

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        $response = $ledgerService->fetchAccountsByEntitiesAndMerchantID($accountPayload, $requestHeaders, true);

        $merchantAccountBalancesList = $response['body']['accounts'];

        return $this->getMerchantAccountBalancesMap($merchantAccountBalancesList);
    }

    private function getMerchantAccountBalancesMap($merchantAccountBalancesList): array
    {
        $accountBalances = [];

        foreach ($merchantAccountBalancesList as $account)
        {
            $fundAccountType = $account[Constants::ENTITIES][Constants::FUND_ACCOUNT_TYPE][0];

            $accountType = $account[Constants::ENTITIES][Constants::ACCOUNT_TYPE][0];

            switch ($fundAccountType)
            {
                case Constants::MERCHANT_FEE_CREDITS:
                    $accountBalances[Constants::MERCHANT_FEE_CREDITS] = $account[Constants::BALANCE];
                    break;

                case Constants::REWARD:
                    if ($accountType == Constants::PAYABLE)
                    {
                        $accountBalances[Constants::MERCHANT_AMOUNT_CREDITS] = $account[Constants::BALANCE];
                    }
                    break;

                case Constants::MERCHANT_BALANCE:
                    $accountBalances[Constants::MERCHANT_BALANCE] = $account[Constants::BALANCE];
                    break;
            }
        }
        return $accountBalances;
    }

    protected function prepareOutboxPayload($payloadName, $payloadSerialized)
    {
        $payloadString = json_encode($payloadSerialized);

        $encodedPayload = base64_encode($payloadString);

        $outboxPayload = new LedgerOutboxEntity();

        $outboxPayload->generateId();

        $outboxPayload->build([
            LedgerOutboxEntity::PAYLOAD_NAME        => $payloadName,
            LedgerOutboxEntity::PAYLOAD_SERIALIZED  => $encodedPayload,
        ]);

        return $outboxPayload;
    }

    protected function saveToLedgerOutbox($outboxPayload, $transactor_event)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $repo = $app['repo'];

        try
        {
            $repo->ledger_outbox->saveOrFail($outboxPayload);

            $trace->count(Metric::PG_LEDGER_OUTBOX_PUSH_SUCCESS, [
                Constants::TRANSACTOR_EVENT => $transactor_event
            ]);

            $trace->info(
                TraceCode::PG_LEDGER_OUTBOX_PUSH_SUCCESS,
                [
                    LedgerOutboxConstants::PAYLOAD   => $outboxPayload,
                ]
            );
        }
        catch (\Throwable $ex)
        {
            $trace->count(Metric::PG_LEDGER_OUTBOX_PUSH_FAILURE, [
                Constants::TRANSACTOR_EVENT => $transactor_event
            ]);

            $trace->traceException(
                $ex,
                500,
                TraceCode::PG_LEDGER_OUTBOX_PUSH_FAILURE,
                [
                    LedgerOutboxConstants::PAYLOAD   => $outboxPayload,
                ]);

            throw $ex;
        }
    }

    private function getAccountBalancePayload($merchantId) :array
    {
        return [
            Constants::MERCHANT_ID => $merchantId,
            Constants::ENTITIES => [
                // PG Merchant Balance Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::MERCHANT_BALANCE]
                ],
                // PG Merchant Fee credit Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::MERCHANT_FEE_CREDITS]
                ],
                // PG Merchant Amount Credit Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::REWARD]
                ],
            ],
        ];
    }

    private function getJournalByTransactorInfo(string $transactorId, string $transactorEvent, $ledgerService)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $fetchJournalPayload = [
            Constants::TRANSACTOR_ID        => $transactorId,
            Constants::TRANSACTOR_EVENT     => $transactorEvent
        ];

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        try
        {
            $response = $ledgerService->fetchByTransactor($fetchJournalPayload, $requestHeaders, true);

            return $response['body'];
        }
        catch(\Exception $e)
        {
            $trace->debug(TraceCode::FETCH_JOURNAL_FAILED, [
                Constants::MESSAGE               => $e->getMessage(),
                LedgerOutboxConstants::PAYLOAD   => $fetchJournalPayload
            ]);

            return null;
        }
    }

    private function getJournalRequestHeadersSync($idempotencyKey = null): array
    {
        if($idempotencyKey === null)
        {
            $idempotencyKey = Uuid::uuid1();
        }

        return [
            LedgerService::LEDGER_TENANT_HEADER         => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER       => $idempotencyKey,
            LedgerService::LEDGER_INTEGRATION_MODE_HEADER   => Constants::REVERSE_SHADOW,
        ];
    }

    private function createJournalInLedger(array $journalPayload) : array
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST, $journalPayload);

        $ledgerService = $app['ledger'];

        $requestHeaders = $this->getJournalRequestHeadersSync();

        $retryAttempts = 0;

        $toRetry = true;

        while (($toRetry === true) and
            ($retryAttempts <= LedgerReverseShadowConstants::MAX_RETRY_COUNT))
        {
            try
            {
                $response = $ledgerService->createJournal($journalPayload, $requestHeaders, true);

                $responseBody = $response[LedgerService::RESPONSE_BODY];

                $trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE, $response);

                return $responseBody;

            }
            catch (\Exception $e)
            {
                $err = $e->getError() ? $e->getError()->toPublicArray() : [];
                $toRetry = $this->handleSyncLedgerJournalCreateFailures($journalPayload,  $err, LedgerOutboxConstants::SYNC);

                if ($toRetry === true)
                {
                    $retryAttempts++;
                }

                if(($toRetry === false) or ($retryAttempts > LedgerReverseShadowConstants::MAX_RETRY_COUNT))
                {
                    throw $e;
                }
            }
        }

        return [];
    }


    /**
     * @throws Exception
     */
    private function createRefundJournalInLedger(array $journalPayload) : array
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info(TraceCode::LEDGER_CREATE_REFUND_JOURNAL_ENTRY_REQUEST, $journalPayload);

        $ledgerService = $app['ledger'];

        $requestHeaders = $this->getJournalRequestHeadersSync();

        try
        {
            $response = $ledgerService->createJournal($journalPayload, $requestHeaders, true);

            $responseBody = $response[LedgerService::RESPONSE_BODY];

            $trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE, $response);

            return $responseBody;
        }
        catch (\Exception $e)
        {
            $trace->traceException($e);
            $trace->debug(TraceCode::LEDGER_JOURNAL_CREATE_ERROR, [
                Constants::MESSAGE          => $e->getMessage(),
                Constants::JOURNAL_PAYLOAD  => $journalPayload
            ]);
            throw $e;
        }
    }

    // handles errors in journal creation in pg_legder sync and cron flows
    private function handleSyncLedgerJournalCreateFailures(array $journalPayload, array $errorResponse, string $source = ""): bool
    {
        $app = App::getFacadeRoot();

        $trace  = $app['trace'];

        //Todo: Check if key exists in case API transaction failure
        $errorResponse = $errorResponse['error'] ?? [];
        $errorMessage =  $errorResponse[Error::DESCRIPTION];

        $transactorId = $journalPayload[Constants::TRANSACTOR_ID];

        $transactorEvent = $journalPayload[Constants::TRANSACTOR_EVENT];

        // Non-Recoverable errors cannot be retried, hence soft deleted from the outbox table
        foreach (LedgerReverseShadowConstants::NON_RETRYABLE_ERROR_CODES as $nonRetryableError)
        {
            if (str_contains($errorMessage, $nonRetryableError) === true)
            {
//                if ($nonRetryableError === LedgerReverseShadowConstants::BAD_REQUEST_RECORD_ALREADY_EXIST)
//                {
//                    // check if txn exists already for the journal
//                    $existingTxn = $this->repo->transaction->find($journalId);
//
//                    if ($existingTxn === null)
//                    {
//                        $trace->debug(TraceCode::PG_LEDGER_TRANSACTION_NOT_FOUND, [
//                            LedgerOutboxConstants::ERROR_TYPE      => LedgerOutboxConstants::RECOVERABLE_ERROR,
//                            LedgerOutboxConstants::ERROR_MESSAGE   => $errorMessage,
//                            Constants::JOURNAL_ID                  => $journalId,
//                            Constants::TRANSACTOR_EVENT            => $transactorEvent,
//                            LedgerOutboxConstants::SOURCE          => $source,
//                        ]);
//
//                        // returning true so that txn creation is retried
//                        return true;
//                    }
//
//                }

                $trace->info(TraceCode::PG_LEDGER_NON_RETRYABLE_ERROR_SYNC, [
                    LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::NON_RECOVERABLE_ERROR,
                    LedgerOutboxConstants::ERROR_MESSAGE    => $errorMessage,
                    Constants::TRANSACTOR_ID                => $transactorId,
                    Constants::TRANSACTOR_EVENT             => $transactorEvent,
                    LedgerOutboxConstants::SOURCE           => $source,

                ]);

                $trace->count(Metric::LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE, [
                    LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::NON_RECOVERABLE_ERROR,
                    Constants::TRANSACTOR_EVENT             => $transactorEvent,
                    LedgerOutboxConstants::SOURCE           => $source,
                ]);

                // returning false as requests with non recoverable errors should not be retried
                return false;
            }
        }


        // Emitting a separate metric for ledger account not found issue.
        // The metric will trigger an alert to notify users for account creation
        if (str_contains($errorMessage, LedgerReverseShadowConstants::ACCOUNT_DISCOVERY_ACCOUNT_NOT_FOUND_FAILURE) === true)
        {
            $trace->debug(TraceCode::LEDGER_ACCOUNT_NOT_FOUND, [
                LedgerOutboxConstants::ERROR_MESSAGE    => $errorMessage,
                LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::RECOVERABLE_ERROR,
                Constants::TRANSACTOR_ID                => $transactorId,
                Constants::TRANSACTOR_EVENT             => $transactorEvent,
                LedgerOutboxConstants::SOURCE           => $source,
            ]);

            $trace->count(Metric::LEDGER_ACCOUNT_NOT_FOUND, [
                LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::RECOVERABLE_ERROR,
                Constants::TRANSACTOR_EVENT             => $transactorEvent,
                LedgerOutboxConstants::SOURCE           => $source,
                // todo: add account details later
            ]);

            // if source is cron, entry should be retried
            if($source === LedgerOutboxConstants::CRON)
            {
                return true;
            }

            return false;
        }

        if (str_contains($errorMessage, LedgerReverseShadowConstants::ACCOUNT_DISCOVERY_MULTIPLE_ACCOUNTS_FOUND_FAILURE))
        {
            $trace->debug(TraceCode::MULTIPLE_LEDGER_ACCOUNTS_FOUND, [
                LedgerOutboxConstants::ERROR_MESSAGE    => $errorMessage,
                LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::RECOVERABLE_ERROR,
                Constants::TRANSACTOR_ID                => $transactorId,
                Constants::TRANSACTOR_EVENT             => $transactorEvent,
                LedgerOutboxConstants::SOURCE           => $source,
            ]);

            $trace->count(Metric::MULTIPLE_LEDGER_ACCOUNTS_FOUND, [
                LedgerOutboxConstants::ERROR_TYPE       =>LedgerOutboxConstants::RECOVERABLE_ERROR,
                LedgerOutboxConstants::SOURCE           => $source,
                // todo: add account details later
            ]);

            // if source is cron, entry should be retried
            if($source === LedgerOutboxConstants::CRON)
            {
                return true;
            }

            return false;
        }

        $trace->count(Metric::LEDGER_REVERSE_SHADOW_JOURNAL_CREATE_FAILURE, [
            LedgerOutboxConstants::ERROR_TYPE      =>  LedgerOutboxConstants::RECOVERABLE_ERROR,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            LedgerOutboxConstants::SOURCE           => $source,
        ]);

        $trace->debug(TraceCode::PG_LEDGER_RETRYABLE_ERROR_SYNC, [
            LedgerOutboxConstants::ERROR_TYPE       => LedgerOutboxConstants::RECOVERABLE_ERROR,
            LedgerOutboxConstants::ERROR_MESSAGE    => $errorMessage,
            Constants::TRANSACTOR_ID                => $transactorId,
            Constants::TRANSACTOR_EVENT             => $transactorEvent,
            LedgerOutboxConstants::SOURCE           => $source,
        ]);

        return true;
    }

    protected function getAPITransactionId($transactorId)
    {
        $payloadName = $this->getPayloadName($transactorId, Constants::GATEWAY_CAPTURED);

        $gatewayCaptureOutboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadNameWithTrashed($payloadName);

        if (count($gatewayCaptureOutboxEntries) > 0)
        {
            $gatewayCaptureOutboxEntry = $gatewayCaptureOutboxEntries[0];

            //decode base_64 payload
            $gatewayCapturePayload = base64_decode($gatewayCaptureOutboxEntry->getPayloadSerialized());

            $payload = json_decode($gatewayCapturePayload, true);

            return $payload[Constants::API_TXN_ID];
        }
        return null;
    }

    protected function getTransactionMutexresource($payment)
    {
        return $payment->getId()."_transaction";
    }

}
