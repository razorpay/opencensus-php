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
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Base\Entity;
use RZP\Models\Pricing\Fee;
use RZP\Models\Transaction;
use RZP\Models\Ledger\Constants;
use RZP\Models\Merchant\Balance;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Transaction\CreditType;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Transaction\Entity as TransactionEntity;
use RZP\Models\LedgerOutbox\Entity as LedgerOutboxEntity;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;
use RZP\Models\Ledger\ReverseShadow\Constants as LedgerReverseShadowConstants;
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

    public function getMerchantAccountBalances($ledgerService, $merchantId): array
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


    private function createJournalInLedger(array $journalPayload, bool $isBulkJournalRequest = false, bool $isMultipleJournalRequest = false) : array
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

    protected function getAPITransactionId($transactorId, $payment)
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

        $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

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

    private function filterByFundAccountTypeAndEntryType($item, $fundAccountType, $entryType)
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

        $merchantId = $this->getMerchantIdFromLedgerEntries($merchantBalanceLedgerEntry,$merchantFeeCreditsLedgerEntry, $merchantAmountCreditsLedgerEntry, $merchantReceivableLedgerEntry, $merchantVASAmountLedgerEntry);

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

        $credit = 0; $debit = 0;

        if (isset($merchantBalanceLedgerEntry) === true)
        {
            $credit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_CREDIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;

            $debit = $merchantBalanceLedgerEntry[Constants::TYPE] === Constants::ENTRY_TYPE_DEBIT ? $merchantBalanceLedgerEntry[Constants::AMOUNT] : 0;
        }

        $tax =  $taxBalanceLedgerEntry !== null ? $taxBalanceLedgerEntry[Constants::AMOUNT] : 0;

        $fees = $commissionLedgerEntry !== null ? ($commissionLedgerEntry[Constants::AMOUNT] + $tax) : 0;

        $feeCredits =  $merchantFeeCreditsLedgerEntry !== null ?  $merchantFeeCreditsLedgerEntry[Constants::AMOUNT] : 0;

        $creditType = CreditType::DEFAULT;

        if ($merchantFeeCreditsLedgerEntry !== null)
        {
            $creditType = CreditType::FEE;
        }
        else if ($merchantAmountCreditsLedgerEntry !== null)
        {
            $creditType = CreditType::AMOUNT;
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
            TransactionEntity::BALANCE          => (int) $merchantBalanceLedgerEntry[Constants::BALANCE],
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

    private function getMerchantIdFromLedgerEntries($merchantBalanceLedgerEntry, $merchantFeeCreditsLedgerEntry, $merchantAmountCreditsLedgerEntry, $merchantReceivableLedgerEntry, $merchantVASAmountLedgerEntry)
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
            default:
                $res[Constants::TYPE] = "";
                return $res;
        }
    }


    public function transformJournalResponseToTransactionEntityForPayments($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $payment = $this->repo->payment->findOrFail($baseTransactionEntity->getEntityId());

        $primaryBalance = $this->repo->balance->getMerchantBalance($payment->merchant);

        $baseTransactionEntity->setFeeBearer(($payment->getFeeBearer()));

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_ID, $primaryBalance->getId());

        $baseTransactionEntity->setMdr($baseTransactionEntity->getFee());

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        if($baseTransactionEntity->isGratis() === true)
        {
            $pricingRuleId = (new Fee())->getZeroPricingPlanRule($payment)->getId();

            $baseTransactionEntity->setPricingRule($pricingRuleId);
        }

        return $baseTransactionEntity;
    }

    public function transformJournalResponseToTransactionEntityForAdjustment($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

        return $baseTransactionEntity;
    }


    public function transformJournalResponseToTransactionEntityForDispute($journalResponse)
    {
        $baseTransactionEntity = $this->transformJournalResponseToTransactionEntityBase($journalResponse);

        $adjustmentId = $this->getDisputeAdjustmentAsEntityIdFromDisputeId($baseTransactionEntity->getEntityId(), $baseTransactionEntity->getMerchantId());

        $baseTransactionEntity->setType(LedgerOutboxConstants::ADJUSTMENT);

        $baseTransactionEntity->setEntityId($adjustmentId);

        $baseTransactionEntity->setAttribute(TransactionEntity::BALANCE_UPDATED, null);

        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $baseTransactionEntity->setSettledAt($settledAt);

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
        else
        {
            $commissionLedgerEntry = $this->getSpecificLedgerEntryFromJournal($journalResponse,Constants::RECEIVABLE, Constants::RZP_COMMISSION);
        }

        return $commissionLedgerEntry;

    }

    private function getTransactionAmountForTransactionTypeFromJournal($journalResponse, $transactorType)
    {
        if ($transactorType === Transaction\Type::TRANSFER)
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
}


