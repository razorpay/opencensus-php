<?php

namespace RZP\Models\Ledger\ReverseShadow;

use App;
use Carbon\Carbon;
use Exception;
use RZP\Constants\Entity as E;
use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use RZP\Error\Error;
use Ramsey\Uuid\Uuid;
use RZP\Constants\Metric;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment\Constant;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Base\Entity;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Payment\Status;
use RZP\Models\Ledger\Constants;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Transaction\CreditType;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\LedgerOutbox\Entity as LedgerOutboxEntity;
use RZP\Models\Ledger\ReverseShadow\Utility as ClsUtility;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
use RZP\Models\Transfer;

use function PHPUnit\Framework\assertCount;

trait ReverseShadowTrait
{
    protected function generateBaseForJournalEntry(Entity  $entity, $transactionDate = null): array
    {
        if (isset($transactionDate) === false)
        {
            $transactionDate = $entity->getUpdatedAt();
        }

        $currency = "INR";
        if( $entity->merchant !== null)
        {
            $currency = $entity->merchant->getCurrency();
        }

        return array(
            Constants::MERCHANT_ID               => $entity->getMerchantId(),
            Constants::CURRENCY                  => $currency,
            Constants::TRANSACTION_DATE          => $transactionDate,
        );
    }

    public function isEnabledForPaymentFeeTaxPopulation($payment): bool
    {
        if (($payment->isInternational() === true) or
            ($payment->merchant->isLRSFlowEnabled() === true) or
            ($payment->merchant->isLRSTravelCitiFlowEnabled() === true) or
            ($payment->merchant->isOpgspImportEnabled() === true) or
            ($payment->merchant->isJpmcImportFlowEnabled() === true))
        {
            // Post evaluation we checked that the fee calculation is happening correctly. The difference is causes due
            // to currency conversion. We have to remove this check to reflect the same fee in transaction and payment.
            // Putting it behind experiment for ramping up.
            if ($this->splitzEvaluationForRampPaymentFeePopulationForCBPayments($payment))
            {
                return true;
            }

            return false;
        }

        return true;
    }

    public function splitzEvaluationForRampPaymentFeePopulationForCBPayments($payment): bool
    {
        try
        {
            $properties = [
                'id'            => UniqueIdEntity::generateUniqueId(),
                'request_data'  => json_encode(
                    [
                        'merchant_id' => $payment->merchant->getId(),
                    ]),
                'experiment_id' => $this->app['config']->get('app.cross_border_payment_fee_fix_experiment_id'),
            ];
            $response = $this->app['splitzService']->evaluateRequest($properties);

            $variant = $response['response']['variant']['name'] ?? '';

            $this->trace->info(TraceCode::CROSS_BORDER_PAYMENT_FEE_FIX_EXPERIMENT_RESPONSE, [
                'payment_id' => $payment->getId(),
                'splitz_output' => $variant,
            ]);

            return $variant === 'variant_on';
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SPLITZ_ERROR, [
                'merchant_id'   => $payment->merchant->getId(),
                'experiment_id' => $this->app['config']->get('app.cross_border_payment_fee_fix_experiment_id') ?? null
            ]);

            return false;
        }
    }

    protected function isFeeCreditsWithoutCustomerFeeBearer($feeCredits ,$fee, PaymentEntity $payment)
    {
        $customerFee = $payment->getConvenienceFee();
        $customerTax = $payment->getConvenienceFeeGst();

        if((isset($customerFee)) or (isset($customerTax)))
        {
            return (($feeCredits > 0) and ($feeCredits >= ($fee-$customerFee-$customerTax)));
        }

        return (($feeCredits > 0) and ($feeCredits >= $fee) and ($this->isPaymentFeeBearerCustomer($payment) === false));
    }

    protected function isPostPaidDynamicFeeBearerFlag(PaymentEntity $payment,$merchant)
    {
        return (($this->isPostpaid($payment) === true) and ($merchant->isFeeBearerDynamic() === true) and ($payment->isFeeBearerPlatform() === true));
    }

    protected function isPrepaidDynamicFeeBearerFlag(PaymentEntity $payment): bool
    {
        $merchant = $payment->merchant;
        return (($this->isPostpaid($payment) === false) and ($merchant->isFeeBearerDynamic() === true) and ($payment->isFeeBearerPlatform() === true));
    }

    protected function isFeeCredits($feeCredits ,$fee): bool
    {
        return (($feeCredits > 0) and ($feeCredits >= $fee));
    }

    protected function isGratisWithoutCustomerFeeBearer($amountCredits ,$amount, PaymentEntity $payment)
    {
        return (($amountCredits > 0) and ($amount !== 0) and ($this->isPaymentFeeBearerCustomer($payment) === false) and ($amountCredits >= $amount));
    }

    protected function isGratis($amountCredits ,$amount): bool
    {
        return (($amountCredits > 0) and ($amount !== 0) and ($amountCredits >= $amount));
    }

    protected function isRefundCredits($merchant): bool
    {
        return ($merchant->getRefundSource() === RefundSource::CREDITS);
    }

    protected function isPostPaidWithoutCustomerFeeBearer(PaymentEntity $payment)
    {
        return (($this->isPostpaid($payment) === true) and ($this->isPaymentFeeBearerCustomer($payment) === false));
    }

    protected function isPostpaid(PaymentEntity $payment): bool
    {
        return ($payment->merchant->getFeeModel() === Merchant\FeeModel::POSTPAID);
    }

    protected function isPaymentFeeBearerCustomer(PaymentEntity $payment): bool
    {
        return ($payment->getAttribute(PaymentEntity::FEE_BEARER) === Merchant\FeeBearer::CUSTOMER);
    }

    protected function getPayloadName($transactorId, $transactorEvent): string
    {
        return sprintf("%s-%s", $transactorId, $transactorEvent);
    }

    public function createAndSaveLedgerOutboxPayload($transactorId, $transactorEvent, $journalPayload)
    {
        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $outboxPayload = $this->prepareOutboxPayload($payloadName, $journalPayload);

        $this->saveToLedgerOutbox($outboxPayload, $transactorEvent);
    }

    public function getMerchantAccountBalances($ledgerService, $merchantId): array
    {
        $merchantAccountBalancesList = $this->getMerchantAccounts($ledgerService, $merchantId);

        return $this->getMerchantAccountBalancesMap($merchantAccountBalancesList);
    }

    public function getMerchantAccounts($ledgerService, $merchantId): array
    {
        $accountPayload = $this->getAccountBalancePayload($merchantId);

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        $retryAttempts = 0;

        while ($retryAttempts <= LedgerReverseShadowConstants::MAX_RETRY_COUNT_FETCH_MERCHANT_ACCOUNT)
        {
            try
            {
                $response = $ledgerService->fetchAccountsByEntitiesAndMerchantID($accountPayload, $requestHeaders, true);

                return $response['body']['accounts'];
            }
            catch (\Throwable $e)
            {
                if (method_exists($e, 'getData'))
                {
                    $data = $e->getData();

                    if (isset($data['status_code']))
                    {
                        $responseCode = $data['status_code'];

                        if ($responseCode >= 500)
                        {
                            $retryAttempts++;
                            if ($retryAttempts > LedgerReverseShadowConstants::MAX_RETRY_COUNT_FETCH_MERCHANT_ACCOUNT)
                            {
                                throw $e;
                            }

                            $this->trace->info(
                                TraceCode::PG_LEDGER_FETCH_MERCHANT_ACCOUNTS_RETRY_ATTEMPT,
                                [
                                    'retry_count'  => $retryAttempts,
                                ]
                            );

                            continue;

                        } else
                        {
                            throw $e;
                        }
                    }
                }

                if (strpos($e->getMessage(), 'cURL error 28') !== false)
                {
                    $retryAttempts++;
                    if ($retryAttempts > LedgerReverseShadowConstants::MAX_RETRY_COUNT_FETCH_MERCHANT_ACCOUNT)
                    {
                        throw $e;
                    }

                    $this->trace->info(
                        TraceCode::PG_LEDGER_FETCH_MERCHANT_ACCOUNTS_RETRY_ATTEMPT,
                        [
                            'retry_count'  => $retryAttempts,
                        ]
                    );

                    continue;
                }

                throw $e;

            }
        }

        return [];
    }

    public function getMerchantAccountBalancesMap($merchantAccountBalancesList): array
    {
        $accountBalances = [];
        $now = time();

        foreach ($merchantAccountBalancesList as $account)
        {
            $fundAccountType = $account[Constants::ENTITIES][Constants::FUND_ACCOUNT_TYPE][0];

            $accountType = $account[Constants::ENTITIES][Constants::ACCOUNT_TYPE][0];

            switch ($fundAccountType)
            {
                case Constants::MERCHANT_FEE_CREDITS:
                    $accountBalances[Constants::MERCHANT_FEE_CREDITS] = $account[Constants::BALANCE];
                    break;

                case Constants::REWARD_CREDITS:
                case Constants::REWARD:
                    if ($accountType == Constants::PAYABLE)
                    {
                        $entities = $account[Constants::ENTITIES];

                        if (($entities != null) && (empty($entities[Constants::EXPIRED_AT]) === false))
                        {
                            $expiredAt = $entities[Constants::EXPIRED_AT][0];

                            if ($expiredAt < $now)
                            {
                                continue;
                            }
                        }
                        if (isset($accountBalances[Constants::MERCHANT_AMOUNT_CREDITS]))
                        {
                            $accountBalances[Constants::MERCHANT_AMOUNT_CREDITS] += $account[Constants::BALANCE];
                        }
                        else
                        {
                            $accountBalances[Constants::MERCHANT_AMOUNT_CREDITS] = $account[Constants::BALANCE];
                        }
                    }
                    break;

                case Constants::MERCHANT_BALANCE:
                    $accountBalances[Constants::MERCHANT_BALANCE] = $account[Constants::BALANCE];
                    break;

                case Constants::MERCHANT_REFUND_CREDITS:
                    $accountBalances[Constants::MERCHANT_REFUND_CREDITS] = $account[Constants::BALANCE];
                    break;
            }
        }
        return $accountBalances;
    }

    private function getValidAmountCreditsAccounts($merchantAccountBalancesList): array
    {
        $amountCreditsAccounts = [];
        $now = time();

        foreach ($merchantAccountBalancesList as $account)
        {
            $fundAccountType = $account[Constants::ENTITIES][Constants::FUND_ACCOUNT_TYPE][0];

            $accountType = $account[Constants::ENTITIES][Constants::ACCOUNT_TYPE][0];

            if (($fundAccountType == Constants::REWARD_CREDITS) && ($accountType == Constants::PAYABLE))
            {
                $entities = $account[Constants::ENTITIES];
                if (($entities != null) && (empty($entities[Constants::CREDIT_ID]) === false))
                {
                    $expiredAt = $entities[Constants::EXPIRED_AT][0];
                    if ($expiredAt < $now)
                    {
                        continue;
                    }
                    $amountCreditsAccounts[] = $account;
                }
            }
        }

        usort($amountCreditsAccounts, function($a, $b) {
            return $a[Constants::ENTITIES][Constants::EXPIRED_AT][0] <=> $b[Constants::ENTITIES][Constants::EXPIRED_AT][0];
        });
        return $amountCreditsAccounts;
    }

    private function getDynamicMoneyParams($amountCreditsAccounts, $amount)
    {
        $dynamicMoneyParams[Constants::ACCOUNT_DISCOVERY_CONFIG] = [
            Constants::ACCOUNT_CATEGORY  => Constants::LIABILITY,
            Constants::ACCOUNT_TYPE      => Constants::PAYABLE,
            Constants::FUND_ACCOUNT_TYPE => Constants::REWARD_CREDITS,
        ];

        $totalAmount = $amount; // The total amount credits to distribute

        foreach ($amountCreditsAccounts as $account)
        {
            if ($totalAmount <= 0)
            {
                break; // If the total amount is exhausted, break the loop
            }

            $balance = $account['balance'];

            if ($balance <= 0)
            {
                continue; // If the balance is zero, skip the account
            }

            $amountToDeduct = min($balance, $totalAmount);

            $totalAmount -= $amountToDeduct;

            $dynamicMoneyParams[Constants::DYNAMIC_IDENTIFIERS][] = [
                Constants::IDENTIFIERS => [
                    Constants::CREDIT_ID => $account[Constants::ENTITIES][Constants::CREDIT_ID][0],
                ],
                Constants::MONEY_PARAMS => [
                    Constants::AMOUNT_CREDITS => strval($amountToDeduct),
                ],
            ];
        }
        return array($dynamicMoneyParams);
    }

    protected function prepareOutboxPayload($payloadName, $payloadSerialized)
    {
        $entityId = null;
        $entityType = null;

        if(isset($payloadSerialized[Constants::TRANSACTOR_ID]) === true)
        {
            $ledgerOutboxCore = new LedgerOutboxCore();

            $transactorId = $payloadSerialized[Constants::TRANSACTOR_ID];

            $entityId = $ledgerOutboxCore->determineEntityIDFromTransactorID($transactorId);

            $transactorInfo = $ledgerOutboxCore->determineTransactionType($transactorId);

            $entityType = $transactorInfo[Constants::TYPE];
        }

        $payloadString = json_encode($payloadSerialized);

        $encodedPayload = base64_encode($payloadString);

        $outboxPayload = new LedgerOutboxEntity();

        $outboxPayload->generateId();

        $outboxPayload->build([
            LedgerOutboxEntity::PAYLOAD_NAME        => $payloadName,
            LedgerOutboxEntity::PAYLOAD_SERIALIZED  => $encodedPayload,
            LedgerOutboxEntity::ENTITY_TYPE  => $entityType,
            LedgerOutboxEntity::ENTITY_ID  => $entityId,
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
                // PG Merchant Split Account Amount Credit Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::REWARD_CREDITS]
                ],
                // PG Merchant Refund Credit Account
                [
                    Constants::ACCOUNT_TYPE => [Constants::PAYABLE],
                    Constants::FUND_ACCOUNT_TYPE => [Constants::MERCHANT_REFUND_CREDITS]
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

    private function getJournalRequestHeadersSync($idempotencyKey = null, $isAdjustmentLedgerEntryOnly = false): array
    {
        if($idempotencyKey === null)
        {
            $idempotencyKey = Uuid::uuid1();
        }

        $headers = [
            LedgerService::LEDGER_TENANT_HEADER         => Constants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER       => $idempotencyKey,
        ];

        if($isAdjustmentLedgerEntryOnly === false)
        {
            $headers[LedgerService::LEDGER_INTEGRATION_MODE_HEADER] = Constants::REVERSE_SHADOW;
        }

        return $headers;
    }


    public function createJournalInLedger(array $journalPayload, bool $isBulkJournalRequest = false, bool $isMultipleJournalRequest = false, $isAdjustmentLedgerEntryOnly = false) : array
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_REQUEST, $journalPayload);

        $ledgerService = $app['ledger'];

        $requestHeaders = $this->getJournalRequestHeadersSync(null, $isAdjustmentLedgerEntryOnly);

        $retryAttempts = 0;

        $toRetry = true;

        while (($toRetry === true) and
            ($retryAttempts <= LedgerReverseShadowConstants::MAX_RETRY_COUNT))
        {
            try
            {
                if ($isBulkJournalRequest === true)
                {
                    $response = $ledgerService->createBulkJournal($journalPayload, $requestHeaders, true);

                    $responseBody = $response[LedgerService::RESPONSE_BODY]['journals'];
                }
                else if ($isMultipleJournalRequest === true)
                {
                    $response = $ledgerService->createMultipleJournal($journalPayload, $requestHeaders, true);

                    $responseBody = $response[LedgerService::RESPONSE_BODY]['journals'];
                }
                else
                {
                    $response = $ledgerService->createJournal($journalPayload, $requestHeaders, true);

                    $responseBody = $response[LedgerService::RESPONSE_BODY];
                }

                $trace->info(TraceCode::LEDGER_CREATE_JOURNAL_ENTRY_RESPONSE, $responseBody);

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
    public function handleSyncLedgerJournalCreateFailures(array $journalPayload, array $errorResponse, string $source = ""): bool
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

    protected function getAPITransactionId($transactorId, $payment, $transactorEvent = Constants::GATEWAY_CAPTURED)
    {
        $payloadName = $this->getPayloadName($transactorId, $transactorEvent);

        $gatewayCaptureOutboxEntries = $this->repo->ledger_outbox->fetchOutboxEntriesByPayloadNameWithTrashed($payloadName);

        if (count($gatewayCaptureOutboxEntries) > 0)
        {
            $gatewayCaptureOutboxEntry = $gatewayCaptureOutboxEntries[0];

            //decode base_64 payload
            $gatewayCapturePayload = base64_decode($gatewayCaptureOutboxEntry->getPayloadSerialized());

            $payload = json_decode($gatewayCapturePayload, true);

            return $payload[Constants::API_TXN_ID];
        }

        //change connection here fetchBySourceAndAssociateMerchantForConnectionType
        $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchantForConnectionType($payment, null);

        if (isset($txn) === true)
        {
            return $txn->getId();
        }

        return null;
    }

    protected function getTransactionMutexresource($baseEntity)
    {
        return $baseEntity->getId()."_transaction";
    }

    public function isNegativeBalanceEnabledForTxnTypeAndMerchant(string $txnType, string $balanceType = Balance\Type::PRIMARY) : bool
    {
        if ((array_key_exists($balanceType, Balance\Core::NEGATIVE_FLOWS) === true) and
            (in_array($txnType, Balance\Core::NEGATIVE_FLOWS[$balanceType]) === true))
        {
            return true;
        }

        return false;
    }

    public function determineJournalIdForAPITransaction($journal, $debitJournalFundAccountType, $creditJournalFuncAccountType)
    {
        $debitJournals = array_filter($journal, function($item) use ($debitJournalFundAccountType) {
            return $this->filterByFundAccountTypeAndEntryType($item, $debitJournalFundAccountType, Constants::ENTRY_TYPE_DEBIT);
        });

        $creditJournals = array_filter($journal, function($item) use ($creditJournalFuncAccountType) {
            return $this->filterByFundAccountTypeAndEntryType($item, $creditJournalFuncAccountType, Constants::ENTRY_TYPE_CREDIT);
        });

        $creditJournalId = '';
        $debitJournalId = '';

        foreach ($debitJournals as $debitJournal) {
            $debitJournalId = $debitJournal['id'];
        }

        foreach ($creditJournals as $creditJournal) {
            $creditJournalId = $creditJournal['id'];
        }

        return [$creditJournalId, $debitJournalId];
    }

    public function filterByFundAccountTypeAndEntryType($item, $fundAccountType, $entryType)
    {
        $searchResults = [];

        if (isset($item['ledger_entry']))
        {
            foreach ($item['ledger_entry'] as $ledgerEntry)
            {
                if (($ledgerEntry['type'] === $entryType) and
                    (isset($ledgerEntry['account_entities']['fund_account_type'])) and
                    (in_array($fundAccountType, $ledgerEntry['account_entities']['fund_account_type'])))
                {
                    $searchResults[] = $item;
                    break;
                }
            }
        }
        return $searchResults;
    }

    public function fetchFundAccountTypeForDebitFromJournal($journalResponse): ?array
    {
        $fundAccountTypes = [];

        if(isset($journalResponse[Constants::LEDGER_ENTRY]) === false)
        {
            return null;
        }

        foreach ($journalResponse[Constants::LEDGER_ENTRY] as $ledgerEntry)
        {
            if ((isset($ledgerEntry[Constants::TYPE]) === true) and
                ($ledgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_DEBIT))
            {
                if ((isset($ledgerEntry[Constants::ACCOUNT_ENTITIES]) === true) and
                    (isset($ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::FUND_ACCOUNT_TYPE]) === true))
                {
                    $fundAccountTypes [] = $ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::FUND_ACCOUNT_TYPE][0];
                }
            }
        }

        return (empty($fundAccountTypes) === false) ? $fundAccountTypes : null ;
    }

    public function fetchMerchantBalanceOnly($merchant){

        $ledgerService = $this->app['ledger'];

        $merchantAccountBalances = $this->getMerchantAccountBalance($ledgerService, $merchant->getId());

        return $merchantAccountBalances[Constants::MERCHANT_BALANCE];

    }

    public function getFeeAndTaxFromJournal($journal, $commissionFundAccountType, $taxFundAccountType)
    {
        $tax = 0;
        $commission = 0;
        $isAmountCreditsUsed = false;

        if (isset($journal['ledger_entry'])) {
            foreach ($journal['ledger_entry'] as $ledgerEntry)
            {

                if(isset($ledgerEntry['account_entities']) === true)
                {
                    $fundAccountType = [];
                    $accountType = [];

                    if (isset($ledgerEntry['account_entities']['fund_account_type']) === true)
                    {
                        $fundAccountType = $ledgerEntry['account_entities']['fund_account_type'];
                    }

                    if (isset($ledgerEntry['account_entities']['account_type']) === true)
                    {
                        $accountType = $ledgerEntry['account_entities']['account_type'];
                    }

                    $amount = intval($ledgerEntry['amount']);

                    if(in_array($taxFundAccountType,$fundAccountType))
                    {
                        $tax = $amount;
                    }

                    if(in_array($commissionFundAccountType,$fundAccountType))
                    {
                        $commission = $amount;
                    }

                    if((in_array("reward", $fundAccountType))
                        and (in_array("payable", $accountType)))
                    {
                        $isAmountCreditsUsed = true;
                    }
                }
            }
        }

        return [$tax+$commission, $tax, $isAmountCreditsUsed];
    }

    public function transformJournalResponseToTransactionEntityBase($journalResponse)
    {
        $transactorPublicId = $journalResponse[Constants::TRANSACTOR_ID];

        $currency = $journalResponse[Constants::CURRENCY];

        $transactorInfo = $this->determineTransactionTypeFromTransactorId($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $transactorId =  $transactorInfo[Constants::ID];

        $merchantBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_BALANCE_FUND_ACCOUNT);

        $merchantFeeCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_FEE_CREDITS);

        $merchantAmountCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::REWARD);

        $merchantVASAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::MERCHANT_VAS_ACCOUNT);

        $transactionAmount = $this->getTransactionAmountForTransactionTypeFromJournal($journalResponse,$transactionType);

        $commissionLedgerEntry = $this->getCommissionLedgerEntryForTransactionTypeFromJournal($journalResponse, $transactionType);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::RZP_GST);

        $merchantReceivableLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::MERCHANT_INVOICE);

        // Here, for new split account feature of merchants amount credit accounts.
        // Adding a check if the previous entry amount credit entry was nil, inferring, either amount credit was not used ot the new account was used.
        if ($merchantAmountCreditsLedgerEntry === null) {
            // Although ledger entry in journal can have multiple amount credit account, but we are fetching only the first one.
            // As the only use case here is adding in entity if it is gratis or not.
            $merchantAmountCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::REWARD_CREDITS);
	    }

        $merchantRefundCreditLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_REFUND_CREDITS);

        $merchantGmvLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_GMV);

        $merchantReserveBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_RESERVE_BALANCE);

        $merchantId = $this->getMerchantIdFromLedgerEntries($merchantBalanceLedgerEntry,$merchantFeeCreditsLedgerEntry, $merchantAmountCreditsLedgerEntry, $merchantReceivableLedgerEntry, $merchantVASAmountLedgerEntry,
            $merchantRefundCreditLedgerEntry, $merchantGmvLedgerEntry, $merchantReserveBalanceLedgerEntry);

        if ($merchantId === null)
        {
            $this->trace->info(TraceCode::MISSING_MERCHANT_ID_LEDGER_ENTRIES,
                [
                    'journal'               => $journalResponse,
                ]);
        }
        else
        {
            $merchant = $this->repo->merchant->findOrFail($merchantId);
        }

        $credit = 0; $debit = 0; $feeCredits = 0; $balance = 0;

        if (isset($merchantBalanceLedgerEntry) === true)
        {
            $credit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_CREDIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

            $debit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_DEBIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

            $balance = $merchantBalanceLedgerEntry[Constants::BALANCE];
        }
        else if (isset($merchantReserveBalanceLedgerEntry) === true)
        {
            $credit = $merchantReserveBalanceLedgerEntry[Constants::AMOUNT];
            $balance = $merchantReserveBalanceLedgerEntry[Constants::BALANCE];
        }

        $tax =  $taxBalanceLedgerEntry !== null ? $taxBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $fees = $commissionLedgerEntry !== null ? ($commissionLedgerEntry[Constants::AMOUNT] + $tax) : 0;

        $creditType = CreditType::DEFAULT;

        if ($merchantFeeCreditsLedgerEntry !== null)
        {
            $creditType = CreditType::FEE;

            $feeCredits = $merchantFeeCreditsLedgerEntry[Constants::AMOUNT];
        }
        else if ($merchantAmountCreditsLedgerEntry !== null)
        {
            $creditType = CreditType::AMOUNT;
        }
        else if ($merchantRefundCreditLedgerEntry !== null)
        {
            $creditType = CreditType::REFUND;

            $feeCredits = $merchantRefundCreditLedgerEntry[Constants::AMOUNT];
        }

        $feeModel = $merchantReceivableLedgerEntry !== null ? Merchant\FeeModel::POSTPAID : Merchant\FeeModel::PREPAID;

        $transaction = [
            TransactionEntity::ID               => $journalResponse[Constants::ID],
            TransactionEntity::ENTITY_ID        => $transactorId,
            TransactionEntity::TYPE             => $transactionType,
            TransactionEntity::MERCHANT_ID      => $merchantId,
            TransactionEntity::AMOUNT           => (int) $transactionAmount,
            TransactionEntity::CURRENCY         => $currency,
            TransactionEntity::CREDIT           => (int) $credit,
            TransactionEntity::DEBIT            => (int) $debit,
            TransactionEntity::BALANCE          => (int) $balance,
            TransactionEntity::FEE              => (int) $fees,
            TransactionEntity::TAX              => (int) $tax,
            TransactionEntity::CHANNEL          => isset($merchant) ? $merchant->getChannel(): null,
            TransactionEntity::CREDITS          => (int) $feeCredits,
            TransactionEntity::CREDIT_TYPE      => $creditType,
            TransactionEntity::BALANCE_ID       => null,
            TransactionEntity::CREATED_AT       => $journalResponse[Constants::CREATED_AT],
            TransactionEntity::UPDATED_AT       => $journalResponse[Constants::UPDATED_AT],
            TransactionEntity::BALANCE_UPDATED  => true,
            TransactionEntity::FEE_BEARER       => Merchant\FeeBearer::NA,
            TransactionEntity::FEE_MODEL        => $feeModel,
            TransactionEntity::API_FEE          => 0,
            TransactionEntity::MDR              => null,
            TransactionEntity::GRATIS           => $merchantAmountCreditsLedgerEntry !== null,
        ];

        $txn = new TransactionEntity();

        $txn->forceFill($transaction);

        return $txn;
    }

    public function getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse)
    {
        $transactorPublicId = $journalResponse[Constants::TRANSACTOR_ID];

        $transactorInfo = $this->determineTransactionTypeFromTransactorId($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $merchantBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_BALANCE_FUND_ACCOUNT);

        $merchantFeeCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_FEE_CREDITS);

        $merchantAmountCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::REWARD);

        $merchantVASAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::MERCHANT_VAS_ACCOUNT);

        $commissionLedgerEntry = $this->getCommissionLedgerEntryForTransactionTypeFromJournal($journalResponse, $transactionType);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::RZP_GST);

        $merchantReceivableLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::MERCHANT_INVOICE);

        $merchantRefundCreditLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_REFUND_CREDITS);

        $merchantGmvLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_GMV);

        $merchantReserveBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::MERCHANT_RESERVE_BALANCE);

        // Here, for new split account feature of merchants amount credit accounts.
        // Adding a check if the previous entry amount credit entry was nil, inferring, either amount credit was not used ot the new account was used.
        if ($merchantAmountCreditsLedgerEntry === null) {
            // Although ledger entry in journal can have multiple amount credit account, but we are fetching only the first one.
            // As the only use case here is adding in entity if it is gratis or not.
            $merchantAmountCreditsLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::REWARD_CREDITS);
        }

        $merchantId = $this->getMerchantIdFromLedgerEntries($merchantBalanceLedgerEntry,$merchantFeeCreditsLedgerEntry, $merchantAmountCreditsLedgerEntry, $merchantReceivableLedgerEntry, $merchantVASAmountLedgerEntry,
            $merchantRefundCreditLedgerEntry, $merchantGmvLedgerEntry, $merchantReserveBalanceLedgerEntry);

        if ($merchantId === null)
        {
            $this->trace->info(TraceCode::MISSING_MERCHANT_ID_LEDGER_ENTRIES,
                [
                    'journal'               => $journalResponse,
                ]);
        }

        $feeCreditUsed = false; $amountCreditUsed = false; $refundCreditUsed = false;

        $tax =  $taxBalanceLedgerEntry !== null ? $taxBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $fees = $commissionLedgerEntry !== null ? ($commissionLedgerEntry[Constants::AMOUNT] + $tax) : 0;

        if ($merchantFeeCreditsLedgerEntry !== null)
        {
            $feeCreditUsed = true;
        }
        else if ($merchantAmountCreditsLedgerEntry !== null)
        {
            $amountCreditUsed = true;
        }
        else if ($merchantRefundCreditLedgerEntry !== null)
        {
            $refundCreditUsed = true;
        }

        return [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed];
    }
    private function getMerchantIdFromLedgerEntries($merchantBalanceLedgerEntry, $merchantFeeCreditsLedgerEntry, $merchantAmountCreditsLedgerEntry,
                                                    $merchantReceivableLedgerEntry, $merchantVASAmountLedgerEntry, $merchantRefundCreditLedgerEntry,
                                                    $merchantGmvLedgerEntry, $merchantReserveBalanceLedgerEntry)
    {
        if(isset($merchantBalanceLedgerEntry) === true)
        {
            return $merchantBalanceLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantReceivableLedgerEntry) === true)
        {
            return $merchantReceivableLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantFeeCreditsLedgerEntry) === true)
        {
            return $merchantFeeCreditsLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantAmountCreditsLedgerEntry) === true)
        {
            return $merchantAmountCreditsLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantVASAmountLedgerEntry) === true)
        {
            return $merchantVASAmountLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantRefundCreditLedgerEntry) === true)
        {
            return $merchantRefundCreditLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantGmvLedgerEntry) === true)
        {
            return $merchantGmvLedgerEntry[Constants::MERCHANT_ID];
        }
        else if (isset($merchantReserveBalanceLedgerEntry) === true)
        {
            return $merchantReserveBalanceLedgerEntry[Constants::MERCHANT_ID];
        }

        return null;
    }

    public function determineTransactionTypeFromTransactorId($transactorId) :array
    {
        $ledgerOutboxCore = new LedgerOutboxCore();

        $transactorIdArr = $ledgerOutboxCore->getTransactorIDArray($transactorId);

        $publicIdPrefix = $transactorIdArr[0];

        $res = [
            Constants::TRANSACTOR_ID => $transactorId,
            Constants::ID            => $transactorIdArr[1]
        ];

        switch ($publicIdPrefix)
        {
            case "pay":
                $res[Constants::TYPE] = LedgerOutboxConstants::PAYMENT;
                return $res;
            case "rfnd":
                $res[Constants::TYPE] = LedgerOutboxConstants::REFUND;
                return $res;
            case "rvrsl":
                $res[Constants::TYPE] = LedgerOutboxConstants::REVERSAL;
                return $res;
            case "adj":
                $res[Constants::TYPE] = LedgerOutboxConstants::ADJUSTMENT;
                return $res;
            case "disp":
                $res[Constants::TYPE] = LedgerOutboxConstants::DISPUTE;
                return $res;
            case "trf":
                $res[Constants::TYPE] = LedgerOutboxConstants::TRANSFER;
                return $res;
            case "credits":
                $res[Constants::TYPE] = LedgerOutboxConstants::CREDIT;
                return $res;
            case "chrg":
                $res[Constants::TYPE] = Transaction\Type::PRODUCT_CHARGE;
                return $res;
            case "bundfee":
                $res[Constants::TYPE] = Transaction\Type::BUNDLE_FEE;
                return $res;
            default:
                $res[Constants::TYPE] = "";
                return $res;
        }
    }


    public function transformJournalResponseToTransactionEntityForPayments($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        [$aggregateFees, $aggregateTax] = $this->getFeeSplitAggregateFeeAndTax($journalResponse);

        $payment = $this->repo->payment->findOrFail($baseTransactionEntity->getEntityId());

        $enableCLSBalanceReads = (new ClsUtility())->isBalanceReadFromClsExperimentEnabled($payment->merchant);

        if ($enableCLSBalanceReads === true)
        {
            $primaryBalance = $this->repo->balance->fetchBalanceByMerchantIdAndTypeFromWarehouse($payment->merchant, Balance\Type::PRIMARY);

            if ($primaryBalance === null)
            {
                $primaryBalance = $this->repo->balance->getMerchantBalance($payment->merchant);
            }
            $primaryBalance->merchant()->associate($payment->merchant);
        }
        else
        {
            $primaryBalance = $this->repo->balance->getMerchantBalance($payment->merchant);
        }

        $baseTransactionEntity->setFee($aggregateFees + $aggregateTax);

        $baseTransactionEntity->setTax($aggregateTax);

        $baseTransactionEntity->setFeeBearer(($payment->getFeeBearer()));

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_ID, $primaryBalance->getId());

        $baseTransactionEntity->setMdr($baseTransactionEntity->getFee());

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        // We are not setting this currently, as usecase has been figured where, org other than RZP org also has
        // Zero pricing rule, causing failures.
//        if($baseTransactionEntity->isGratis() === true)
//        {
//            $pricingRuleId = (new Fee())->getZeroPricingPlanRule($payment)->getId();
//
//            $baseTransactionEntity->setPricingRule($pricingRuleId);
//        }

        $merchant = $payment->merchant;

        if ($merchant->isFeatureEnabled(Feature\Constants::TRANSACTION_ON_HOLD) === true or
            ($merchant->isOpgspImportEnabled() === true and in_array($merchant->getPurposeCode(), Constant::OPGSP_AWB_REQUIRED)) or
            Transaction\Processor\Payment::shouldHoldSubmerchantPayment($payment, $merchant) === true)
        {
            $baseTransactionEntity->setOnHold(true);
        }

        return $baseTransactionEntity;
    }


    public function setPaymentFeeAndTaxAsPerJournal($baseTransactionEntity)
    {
        $payment = $this->repo->payment->findOrFail($baseTransactionEntity->getEntityId());

        if($this->isEnabledForPaymentFeeTaxPopulation($payment) === false)
        {
            return;
        }

        $payment->setAttribute(Payment\Entity::TRANSACTION_ID, $baseTransactionEntity->getId());

        $payment->setTax($baseTransactionEntity->getTax());

        // currently, international cases are blocked on this flow, so we can cleanly just have the fee set for non CFB cases.
        // TODO on this will be to start flowing cross borded payments and have the fee set for them for CFB use cases too.
        ;
        if (($payment->isFeeBearerCustomer() === false) or (($payment->isFeeBearerCustomer() === true) and
                (($payment->isInternational() === true) or
                ($payment->merchant->isLRSFlowEnabled() === true) or
                ($payment->merchant->isLRSTravelCitiFlowEnabled() === true) or
                ($payment->merchant->isOpgspImportEnabled() === true) or
                ($payment->merchant->isJpmcImportFlowEnabled() === true))))
        {
            //set and fee values from txn
            $payment->setFee($baseTransactionEntity->getFee());
        }

        $payment->setMdr($baseTransactionEntity->getFee());

        $this->repo->saveOrFail($payment);
    }

    public function transformJournalResponseToTransactionEntityForAdjustment($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }


    public function transformJournalResponseToTransactionEntityForDispute($journalResponse, $adjustment)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $adjustmentId = $adjustment->getId();

        $baseTransactionEntity->setType(LedgerOutboxConstants::ADJUSTMENT);

        $baseTransactionEntity->setEntityId($adjustmentId);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForReversal($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForPayout($journalResponse, \RZP\Models\Payout\Entity $payout)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $baseTransactionEntity->setChannel($payout->merchant->getChannel());

        $settledAt = $journalResponse[Constants::CREATED_AT];

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForRefund($journalResponse, $refundId)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $baseTransactionEntity->setType(LedgerOutboxConstants::REFUND);

        $baseTransactionEntity->setEntityId($refundId);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForCustomerTransfer($journal, Transfer\Entity $transfer)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journal);

        $commissionLedgerEntry =$this->getCommissionLedgerEntryForTransactionTypeFromJournal($journal, Transaction\Type::TRANSFER);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journal,Constants::PAYABLE, Constants::RZP_GST);

        $apiFee = $commissionLedgerEntry['amount'] + $taxBalanceLedgerEntry['amount'];

        $baseTransactionEntity->setFee($apiFee);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        $baseTransactionEntity->setChannel($transfer->merchant->getChannel());

        $baseTransactionEntity->sourceAssociate($transfer);

        $baseTransactionEntity->setReconciledAt($settledAt);

        $baseTransactionEntity->setReconciledType(Transaction\ReconciledType::NA);

        return $baseTransactionEntity;
    }

    public function getDisputeAdjustmentAsEntityIdFromDisputeId($disputeId, $merchantId)
    {
        $adjustments = $this->repo->adjustment->findAdjustmentByEntityIdAndEntityType($disputeId, E::DISPUTE, $merchantId);

        if ($adjustments !== null && $adjustments->count() >0)
        {
            $adjustment = $adjustments[0];

            return $adjustment->getId();
        }

        return null;
    }


    private function getCommissionLedgerEntryForTransactionTypeFromJournal($journalResponse, $transactorType)
    {
        if ($transactorType === Transaction\Type::TRANSFER)
        {
            $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::CASH, Constants::RZP_TRANSFER_FEE);
        }
        else if ($transactorType === Transaction\Type::PRODUCT_CHARGE)
        {
            $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::PRODUCT_REVENUE);
        }
        else if ($transactorType === Transaction\Type::BUNDLE_FEE)
        {
            $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::PRICING_SUBSCRIPTION);
        }
        else
        {
            $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::RZP_COMMISSION);
        }

        return $commissionLedgerEntry;

    }

    private function getFeeSplitAggregateFeeAndTax($journalResponse)
    {
        $aggregatedFees = 0; $aggregatedTax = 0;

        $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::RZP_COMMISSION);

        $upiInAppCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::UPI_INAPP_COMMISSION);

        $recurringCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::RECURRING_COMMISSION);

        $magicCheckoutCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::MAGIC_CHECKOUT_COMMISSION);

        $optimiserCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::OPTIMIZER_COMMISSION);

        $esAutomaticCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::ESAUTOMATIC_COMMISSION);

        $partnerCommissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::RECEIVABLE, Constants::PARTNER_COMMISSION_FUND_ACCOUNT);

        $taxBalanceLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::PAYABLE, Constants::RZP_GST);

        $partnerTaxLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::PARTNER_TAX_FUND_ACCOUNT);

        $generalPaymentFees = $commissionLedgerEntry !== null ? ($commissionLedgerEntry[Constants::AMOUNT]) : 0;

        $upiInAppFees = $upiInAppCommissionLedgerEntry !== null ? ($upiInAppCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $recurringFees = $recurringCommissionLedgerEntry !== null ? ($recurringCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $magicCheckoutFees = $magicCheckoutCommissionLedgerEntry !== null ? ($magicCheckoutCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $optimiserFees = $optimiserCommissionLedgerEntry !== null ? ($optimiserCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $esAutomaticFees = $esAutomaticCommissionLedgerEntry !== null ? ($esAutomaticCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $partnerFees = $partnerCommissionLedgerEntry !== null ? ($partnerCommissionLedgerEntry[Constants::AMOUNT]) : 0;

        $tax =  $taxBalanceLedgerEntry !== null ? $taxBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $partnerTax = $partnerTaxLedgerEntry !== null ? ($partnerTaxLedgerEntry[Constants::AMOUNT]) : 0;

        $aggregatedFees = $generalPaymentFees + $upiInAppFees + $recurringFees + $magicCheckoutFees + $optimiserFees + $esAutomaticFees + $partnerFees;

        $aggregatedTax = $tax + $partnerTax;

        return [$aggregatedFees, $aggregatedTax];
    }

    private function getTransactionAmountForTransactionTypeFromJournal($journalResponse, $transactorType)
    {
        if ($transactorType == Transaction\Type::TRANSFER &&
        $journalResponse[Constants::TRANSACTOR_EVENT] == Constants::CUSTOMER_WALLET_LOADING)
        {
            $transactionAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::FUND_ACCOUNT_TYPE_CUSTOMER_WALLET);

            return $transactionAmountLedgerEntry['amount'];
        }
        else if ($transactorType === Transaction\Type::TRANSFER)
        {
            $transactionAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::MERCHANT_VA_MERCHANT);

            return $transactionAmountLedgerEntry['amount'];
        }
        else if ($transactorType === Transaction\Type::PAYMENT)
        {
            $transactionAmountLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse, Constants::PAYABLE, Constants::MERCHANT_GMV);

            return $transactionAmountLedgerEntry['amount'];
        }
        else
        {
            return $journalResponse[Constants::BASE_AMOUNT];
        }
    }

    private function getSpecificLedgerEntryFromJournal($journalResponse,$accountType ,$fundAccountType)
    {
        // check if the transaction is on shared or direct balance from ledger's response
        foreach($journalResponse[Constants::LEDGER_ENTRY] as $ledgerEntry)
        {
            if ((empty($ledgerEntry[Constants::ACCOUNT_ENTITIES]) === false) and
                (empty($ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::ACCOUNT_TYPE]) === false) and
                (empty($ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::FUND_ACCOUNT_TYPE]) === false))
            {

                if ($ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::ACCOUNT_TYPE][0] !== $accountType)
                {
                    continue;
                }

                if ($ledgerEntry[Constants::ACCOUNT_ENTITIES][Constants::FUND_ACCOUNT_TYPE][0] === $fundAccountType)
                {
                    return $ledgerEntry;
                }
            }
        }

        return null;
    }

    public function checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabled($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_LEDGER_JOURNAL,
            $this->mode
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_EXP_CHECK,
            [
                'merchant'               => $merchant->getId(),
                'isExperimentEnabled'    => $isExperimentEnabled,
                'type'                   => "transfer"
            ]);

        return $isExperimentEnabled;
    }

    public function checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabledForPayments($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_PAYMENTS,
            $this->mode ?? Mode::LIVE
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_EXP_CHECK,
            [
                'merchant'               => $merchant->getId(),
                'isExperimentEnabled'    => $isExperimentEnabled,
                'type'                   => "payment"
            ]);

        return $isExperimentEnabled;
    }

    /** getAPITxnIDForReverseShadowPayments returns the transaction Id
     * for reverse shadow payment
     * @param PaymentEntity $payment
     * @return mixed|void|null
     */
    public function getAPITxnIDForReverseShadowPayments(PaymentEntity $payment)
    {
        if ($payment->getStatus() === Status::CAPTURED)
        {
           $ledgerService = $this->app['ledger'];

            $journal = $this->getJournalByTransactorInfo($payment->getPublicId(), Constants::MERCHANT_CAPTURED, $ledgerService);

            if ($journal === null)
            {
                return null;
            }

            return $journal['id'];
        }

        return null;
    }

    public function checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabledForAdjustments($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_ADJUSTMENTS,
            $this->mode ?? Mode::LIVE
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_EXP_CHECK,
            [
                'merchant'               => $merchant->getId(),
                'isExperimentEnabled'    => $isExperimentEnabled,
                'type'                   => "adjustment"
            ]);

        return $isExperimentEnabled;
    }

    public function checkIfEarlyDispatchOfTxnForSettlementsExperimentIsEnabledForReversals($merchant): bool
    {
        $variant = App::getFacadeRoot()->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_USING_JOURNAL_REVERSALS,
            $this->mode ?? Mode::LIVE
        );

        $isExperimentEnabled = ($variant === 'on');

        $this->trace->info(TraceCode::EARLY_DISPATCH_OF_TXNS_FOR_SETTLEMENTS_EXP_CHECK,
            [
                'merchant'               => $merchant->getId(),
                'isExperimentEnabled'    => $isExperimentEnabled,
                'type'                   => "reversal"
            ]);

        return $isExperimentEnabled;
    }
    public function createTransactionEntityForPreFundWithdrawFromJournal($journalResponse, $merchant): TransactionEntity
    {
        $transactorPublicId = $journalResponse[Constants::TRANSACTOR_ID];

        $currency = $journalResponse[Constants::CURRENCY];

        $transactorInfo = $this->determineTransactionTypeFromTransactorId($transactorPublicId);

        $transactionType = $transactorInfo[Constants::TYPE];

        $transactorId =  $transactorInfo[Constants::ID];

        $transactionAmount = $journalResponse[Constants::BASE_AMOUNT];


        $transaction = [
            TransactionEntity::ID               => $journalResponse[Constants::ID],
            TransactionEntity::ENTITY_ID        => $transactorId,
            TransactionEntity::TYPE             => $transactionType,
            TransactionEntity::MERCHANT_ID      => $merchant->getId(),
            TransactionEntity::AMOUNT           => (int) $transactionAmount,
            TransactionEntity::CURRENCY         => $currency,
            TransactionEntity::CREDIT           => (int) $transactionAmount,
            TransactionEntity::DEBIT            => 0,
            TransactionEntity::BALANCE          => 0,
            TransactionEntity::FEE              => 0,
            TransactionEntity::TAX              => 0,
            TransactionEntity::CHANNEL          => $merchant->getChannel(),
            TransactionEntity::CREDITS          => 0,
            TransactionEntity::CREDIT_TYPE      => 0,
            TransactionEntity::BALANCE_ID       => null,
            TransactionEntity::CREATED_AT       => $journalResponse[Constants::CREATED_AT],
            TransactionEntity::UPDATED_AT       => $journalResponse[Constants::UPDATED_AT],
            TransactionEntity::BALANCE_UPDATED  => null,
            TransactionEntity::FEE_BEARER       => Merchant\FeeBearer::NA,
            TransactionEntity::FEE_MODEL        => Merchant\FeeModel::PREPAID,
            TransactionEntity::API_FEE          => 0,
            TransactionEntity::MDR              => null,
            TransactionEntity::GRATIS           => false,
        ];

        $txn = new TransactionEntity();

        $txn->forceFill($transaction);

        return $txn;
    }
}


