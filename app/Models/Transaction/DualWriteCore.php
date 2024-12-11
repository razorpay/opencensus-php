<?php

namespace RZP\Models\Transaction;

use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Ledger\Constants;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Jobs\Kafka\PGLedgerDualWriteRetryJob;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Ledger\ReverseShadow\ReverseShadowTrait;
use RZP\Models\LedgerOutbox\Core as LedgerOutboxCore;
use RZP\Models\Payment\Processor\Processor;
use RZP\Services\KafkaProducer;
use RZP\Trace\TraceCode;
use RZP\Models\ChargeCollections\ProductCharge;
use RZP\Models\Growth\BundleFee;
use RZP\Models\Ledger\ReverseShadow\Transfers\Core as TransfersReverseShadowCore;
use RZP\Models\Ledger\ReverseShadow\Transfers\Reversal\Core as TransferReversalCore;
use RZP\Models\Transfer;

class DualWriteCore extends Base\Core
{
    use ReverseShadowTrait;
    const PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT = "PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT";

    const MAX_RETRY_COUNT                        = 5;
    const RETRY_ATTEMPT_COUNT                    = 'retry_attempt_count';
    const PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT_TTL_IN_SEC = 10800;

    const ENTITY_TRANSACTION_CREATION_MUTEX_TTL = 60;
    const ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES = 40;
    const ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY = 100;
    const ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY = 200;

    const LEDGER_POSTED_EVENTS = [
        'payment_ledger_posted',
        'refund_ledger_posted'
    ];

    protected $merchant;

    protected $mutex;

    protected $cache;
    public function __construct()
    {
        parent::__construct();

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->mutex = $this->app['api.mutex'];

        $this->cache = $this->app['cache'];
    }

    public function validateAttemptsAndProcessLedgerDualWrite(array $kafkaPayload, string $jobName)
    {
        try
        {
            $payload = $this->identifyPayloadTypeAndReturnDataPayload($kafkaPayload);

            //Not a valid event
            if ($payload === null)
            {
                return;
            }

            $identifier = $payload['id'];

            // bulk journals are embedded inside payload
            if($identifier==='' || $identifier===null)
            {
                $identifier=$payload[LedgerConstants::JOURNALS][0][LedgerConstants::TRANSACTOR_ID];
            }

            if ($this->isKafkaMessageProcessingAttemptExceeded($identifier, $kafkaPayload,$jobName, self::PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT) === true)
            {
                return;
            }

            $this->incrementKafkaMessageProcessingAttempt($identifier,self::PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT, $jobName);

            $this->processLedgerDualWrite($payload);
        }
        catch(\Throwable $e)
        {
            $this->trace->count(Metric::PG_LEDGER_KAFKA_DUAL_WRITE_FAILED);

            $this->trace->traceException(
                $e,
                null,
                TraceCode::PG_LEDGER_DUAL_WRITE_ERROR);

            throw $e;
        }
    }

    /**
     * @param string $identifier
     * @param array  $payload
     *
     * @return bool
     */
    protected function isKafkaMessageProcessingAttemptExceeded(string $identifier, array $payload, string $jobName, string $attribute): bool
    {
        $retryAttemptsCount = $this->getPgLedgerDualWriteAttempts($identifier, $attribute,$jobName);

        $retryAttemptMetrics = [
            self::RETRY_ATTEMPT_COUNT => $retryAttemptsCount
        ];

        $isRetryMessage = str_contains($jobName,'retry_job');

        $this->trace->count(Metric::PG_LEDGER_DUAL_WRITE_RETRY_COUNT_ATTEMPT, $retryAttemptMetrics);

        if ($retryAttemptsCount >= self::MAX_RETRY_COUNT )
        {
            if ($isRetryMessage)
            {
                // retry topic messages have no retry limit, and are re-pushed after every MAX_RETRY_COUNT.
                // unset redis key for inf retries in batches of 5
                $this->unsetPgLedgerDualWriteAttempts($identifier,$attribute,$jobName);
            }

            $this->pushToApiLedgerRetryTopic($payload);

            $this->trace->info(TraceCode::PG_LEDGER_DUAL_WRITE_JOB_RETRY_EXCEEDED, ['payload'=>$payload, 'retry_attempts' => $retryAttemptsCount]);

            return true;
        }

        $this->trace->info(TraceCode::PG_LEDGER_DUAL_WRITE_JOB_PROCESSING_ALLOWED, [
            'payload'           => $payload,
            'retry_attempts'    => $retryAttemptsCount,
            'identifier'        => $identifier,
            'job_name'          => $jobName,
            'is_retry_message'  => $isRetryMessage
        ]);


        return false;
    }

    /**
     * Increment the retry count.
     *
     * @param string $identifier
     */
    protected function incrementKafkaMessageProcessingAttempt(string $identifier, string $attribute,string $jobName): void
    {
        $pgLedgerDualWriteAttempt = $this->getPgLedgerDualWriteAttempts($identifier, $attribute,$jobName);

        $this->updatePgLedgerDualWriteAttempts($identifier, $pgLedgerDualWriteAttempt + 1, $attribute,$jobName);
    }

    /**
     * @param string $identifier
     *
     * @return int return the retry count for the validationId
     */
    protected function getPgLedgerDualWriteAttempts(string $identifier, string $attribute,string $jobName): int
    {
        $pgLedgerDualWriteAttemptKey = $this->getPgLedgerDualWriteAttemptKey($identifier, $attribute,$jobName);

        return $this->cache->get($pgLedgerDualWriteAttemptKey) ?? 0;
    }

    /**
     * Redis Key for PG Ledger Dual Write Id retry.
     *
     *
     * @return string
     */
    protected function getPgLedgerDualWriteAttemptKey(string $identifier, string $attribute,string $jobName): string
    {
        return $attribute . $identifier.$jobName;
    }

    /**
     * Updates the redis key with the retry count
     *
     */
    protected function updatePgLedgerDualWriteAttempts(string $identifier, int $count, string $attribute,string $jobName): void
    {
        $pgLedgerDualWriteAttemptRedisKey = $this->getPgLedgerDualWriteAttemptKey($identifier, $attribute,$jobName);

        $this->cache->put($pgLedgerDualWriteAttemptRedisKey, $count, self::PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    protected function unsetPgLedgerDualWriteAttempts(string $identifier, string $attribute,string $jobName): void
    {
        $pgLedgerDualWriteAttemptRedisKey = $this->getPgLedgerDualWriteAttemptKey($identifier, $attribute,$jobName);

        $this->cache->put($pgLedgerDualWriteAttemptRedisKey, 0, self::PG_LEDGER_DUAL_WRITE_ATTEMPT_COUNT_TTL_IN_SEC);
    }

    private function identifyPayloadTypeAndReturnDataPayload(array $payload)
    {
        //Debezium Based Payload
        if (isset($payload['after']) === true)
        {
            $outboxPayload= $payload['after'];
        }

        //API based events
        else if (isset($payload['data']) === true &&  isset($payload['task_name']) === true)
        {
            if (isset($payload['data']['payload_api']['journal'])===true)
            {
                return $payload['data']['payload_api']['journal'];
            }
            else if ($payload['task_name']===Constants::DUAL_WRITE_TRANSACTION_FOR_API_EVENTS)
            {
                $dataPayload =null;

                if (isset($payload['data']['payload_api'])===true)
                {
                    $dataPayload=$payload['data']['payload_api'];
                }

               return $dataPayload;
            }
        }

        // Maxwell based Payload
        else if (isset($payload['data']) === true)
        {
            $outboxPayload= $payload['data'];
        }

        $serialisedPayload = $outboxPayload['payload'];

        $decodedPayload = base64_decode($serialisedPayload);

        $eventData = json_decode($decodedPayload, true);

        if (isset($eventData['event']['data']['journal_response']) === true
            && in_array($eventData['event']['name'], self::LEDGER_POSTED_EVENTS, true)===true)
        {
            return $eventData['event']['data']['journal_response'];
        }

        return null;
    }

    private function processLedgerDualWrite($journalResponse)
    {
        if (isset($journalResponse[LedgerConstants::TRANSACTOR_EVENT]) === true)
        {
            $transactorEvent = $journalResponse[LedgerConstants::TRANSACTOR_EVENT];

            switch ($transactorEvent)
            {
                case LedgerConstants::PAYMENT_MERCHANT_CAPTURED:
                case LedgerConstants::RAZORPAY_ACCOUNT_METHOD_CREDIT:
                case LedgerConstants::PAYMENT_MERCHANT_CAPTURED_IN_PERSON:
                    $this->createMerchantCaptureAPILedger($journalResponse, $transactorEvent);
                    break;
                case LedgerConstants::REFUND_PROCESSED:
                case LedgerConstants::DISPUTE_REFUND_PROCESSED:
                    $this->createRefundAPILedger($journalResponse);
                    break;
                case LedgerConstants::CUSTOMER_WALLET_LOADING:
                    $this->createCustomerWalletAPILedger($journalResponse);
                    break;
                case LedgerConstants::PRICING_SUBSCRIPTION_CHARGE:
                    $this->createPricingSubscriptionAPILedger($journalResponse);
                    break;
                case LedgerConstants::RAZORPAY_ACCOUNT_METHOD_DEBIT:
                    $this->createAccountMethodDebitAPILedger($journalResponse);
                    break;
                case LedgerConstants::REFUND_REVERSAL:
                    $this->createRefundReversalAPILedger($journalResponse);
                    break;
                case LedgerConstants::RAZORPAY_DISPUTE_DEDUCT:
                case LedgerConstants::POSITIVE_ADJUSTMENT:
                case LedgerConstants::NEGATIVE_ADJUSTMENT:
                case LedgerConstants::RAZORPAY_DISPUTE_REVERSAL:
                    $this->createAdjustmentAPILedger($journalResponse);
            }
        }
        else if(isset($journalResponse[LedgerConstants::JOURNALS]) and is_array($journalResponse[LedgerConstants::JOURNALS]) === true)
        {
            if (isset($journalResponse['reversal_and_refund_journal_ids']))
            {
                $resource = md5(json_encode($journalResponse));

                $this->mutex->acquireAndReleaseStrict($resource, function () use ($journalResponse)
                {
                    $this->repo->transaction(function () use ($journalResponse)
                    {
                        $this->createTransferReversalAndRefundAPILedger($journalResponse);
                    });
                });

                return;
            }

            $bulkJournals = $journalResponse[LedgerConstants::JOURNALS];

            $singleJournal = $bulkJournals[0];

            $transactorEvent = $singleJournal[LedgerConstants::TRANSACTOR_EVENT];

            switch ($transactorEvent)
            {
                case LedgerConstants::TRANSFER_PROCESSED:
                    $this->createTransferAPILedger($journalResponse);
                    break;
            }
        }
    }

    private function createMerchantCaptureAPILedger($journalResponse, $transactorEvent)
    {
        $transactorPublicId = $journalResponse[LedgerConstants::TRANSACTOR_ID];

        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $properties = [
            'request_data' => json_encode(["merchant_id" => $merchant->getId()]),
            'id'            => $merchant->getId(),
            'experiment_id' => $this->app['config']->get('app.api_ledger_dual_write_rearch'),
        ];

        // dual write re-arch enabled for both child+parent if enabled for parent
        $dualWriteRearchEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'enable');

        if ($dualWriteRearchEnabled === false)
        {
            return;
        }

        $payment = $this->repo
            ->payment
            ->findByPublicIdAndMerchant($transactorPublicId, $merchant, []);

        $resource = $this->getTransactionMutexresource($payment);

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($payment, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed, $transactorEvent)
            {
                return $this->repo->transaction(function() use ($payment, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed, $transactorEvent)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($payment);

                    if ((isset($txn) === true) and
                        ($txn->isBalanceUpdated() === true))
                    {
                        return $txn;
                    }

                    if ((isset($txn) === true) and
                        ($txn->getId() !== $journalId))
                    {
                        $journalId = $txn->getId();

                        $this->trace->count(Metric::PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH,
                            [
                                LedgerConstants::TRANSACTOR_EVENT => $transactorEvent,
                            ]
                        );
                    }

                    $txnCore = new Core();

                    list($txn, $feeSplit) = $txnCore->createPaymentTransactionForDualWrite($payment, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::TRANSACTION_CREATED,
            [
                'charge_id'         => $payment->getId(),
                'transaction_id'    => $txn->getId(),
                'event'             => $transactorEvent,
            ]);

        return $txn;
    }

    private function createRefundAPILedger($journalResponse)
    {
        $transactorPublicId = $journalResponse[LedgerConstants::TRANSACTOR_ID];

        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $transactorInfo = $this->determineTransactionTypeFromTransactorId($transactorPublicId);

        $refundId =  $transactorInfo[LedgerConstants::ID];

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $refundId.'_transaction',
            function () use ($refundId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
            {
                return $this->repo->transaction(function() use ($refundId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
                {

                    $txn = $this->repo->transaction->findByEntityId($refundId, $merchant);

                    if($txn !== null)
                    {
                        $this->trace->info(TraceCode::SCROOGE_REFUND_TRANSACTION_ALREADY_EXISTS, $journalResponse);
                    }

                    $refund = $this->repo->refund->findOrFail($refundId);

                    $txnCore = new Core();

                    list($txn, $feesSplit) = $txnCore->createTransactionForSourceDualWrite($refund, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::REFUND_TRANSACTION_CREATE_SUCCESS, [
            'id'         => $txn->getId(),
            'refund_id'  => $refundId,
        ]);
    }

    private function createCustomerWalletAPILedger($journalResponse)
    {
        $journalId = $journalResponse[LedgerConstants::ID];

        $transactorPublicId = $journalResponse[LedgerConstants::TRANSACTOR_ID];

        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $transfer = $this->repo->transfer->findByPublicId($transactorPublicId);

        $resource = $this->getTransactionMutexresource($transfer);

        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($transfer, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed)
            {
                return $this->repo->transaction(function () use ($transfer, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed) {

                    $txnCore = new Core();

                    list($txn, $feeSplit) = $txnCore->createTransferTransactionForDualWrite($transfer, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, false);

                    $transfer->transaction()->associate($txn);

                    $transfer->save();

                    return $txn;
                });
            },
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::PAYMENT_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->trace->info(TraceCode::CUSTOMER_TRANSFER_TRANSACTION_CREATED,
            [
                'transaction_id' => $txn->getId(),
                'source'         => 'dual_write_job'
            ]);

        return $txn;
    }

    private function createPricingSubscriptionAPILedger($journalResponse)
    {
        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $entityId = $journalResponse[LedgerConstants::TRANSACTOR_ID];
        $entityId = BundleFee\Entity::verifyIdAndSilentlyStripSign($entityId);
        $currency = $journalResponse[LedgerConstants::CURRENCY];

        $bundleFee = new BundleFee\Entity;

        $bundleFeeInput = [
            BundleFee\Entity::ID          => $entityId,
            BundleFee\Entity::BASE_AMOUNT => $fees,
            BundleFee\Entity::CURRENCY    => $currency,
            BundleFee\Entity::AMOUNT      => $fees,
            BundleFee\Entity::MERCHANT_ID => $merchantId,
            BundleFee\Entity::IS_REVERSAL => false,
            BundleFee\Entity::TAX         => $tax,
        ];
        $bundleFee->fill($bundleFeeInput);
        $bundleFee->merchant()->associate($merchant);

        $resource = $this->getTransactionMutexresource($bundleFee);

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($bundleFee, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
            {
                return $this->repo->transaction(function() use ($bundleFee, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($bundleFee);

                    if ((isset($txn) === true) and
                        ($txn->getId() !== $journalId))
                    {
                        $journalId = $txn->getId();

                        $this->trace->count(Metric::PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH,
                            [
                                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::PRICING_SUBSCRIPTION_CHARGE,
                            ]
                        );
                    }

                    $txnCore = new Core();

                    list($txn, $feeSplit) = $txnCore->createTransactionForSourceDualWrite($bundleFee, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::GROWTH_TRANSACTION_CREATED,
            [
                'charge_id'         => $bundleFee->getId(),
                'transaction_id'    => $txn->getId(),
            ]);

        return $txn;
    }

    private function createAccountMethodDebitAPILedger($journalResponse)
    {
        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $entityId = $journalResponse[LedgerConstants::TRANSACTOR_ID];
        $entityId = ProductCharge\Entity::verifyIdAndSilentlyStripSign($entityId);
        $currency = $journalResponse[LedgerConstants::CURRENCY];

        $charge = new ProductCharge\Entity;
        $chargeInput = [
            ProductCharge\Entity::ID => $entityId,
            ProductCharge\Entity::BASE_AMOUNT => $fees,
            ProductCharge\Entity::CURRENCY    => $currency,
            ProductCharge\Entity::AMOUNT      => $fees,
            ProductCharge\Entity::MERCHANT_ID => $merchantId,
            ProductCharge\Entity::IS_REVERSAL => false,
            ProductCharge\Entity::TAX         => $tax,
        ];
        $charge->fill($chargeInput);
        $charge->merchant()->associate($merchant);

        $resource = $this->getTransactionMutexresource($charge);

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($charge, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $refundCreditUsed, $amountCreditUsed)
            {
                return $this->repo->transaction(function() use ($charge, $journalId, $merchant,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
                {
                    $txn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($charge);

                    if ((isset($txn) === true) and
                        ($txn->getId() !== $journalId))
                    {
                        $journalId = $txn->getId();

                        $this->trace->count(Metric::PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH,
                            [
                                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::RAZORPAY_ACCOUNT_METHOD_DEBIT,
                            ]
                        );
                    }

                    $txnCore = new Core();

                    list($txn, $feeSplit) = $txnCore->createTransactionForSourceDualWrite($charge, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::CHARGE_COLLECTIONS_TRANSACTION_CREATED,
            [
                'charge_id'         => $charge->getId(),
                'transaction_id'    => $txn->getId(),
            ]);

        return $txn;
    }

    private function createRefundReversalAPILedger($journalResponse)
    {
    }

    private function createAdjustmentAPILedger($journalResponse)
    {
        $journalId = $journalResponse[LedgerConstants::ID];

        $adjustmentId = $journalResponse[LedgerConstants::ADJUSTMENT_ID];

        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $adjustment = $this->repo->adjustment->findByPublicId($adjustmentId);

        $resource = $this->getTransactionMutexresource($adjustment);

        // Using same configs for mutex as payment
        $txn = $this->mutex->acquireAndRelease(
            $resource,
            function () use ($adjustment, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed)
            {
                return $this->repo->transaction(function () use ($adjustment, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed) {

                    $txnCore = new Core();

                    list($txn, $feeSplit) = $txnCore->createAdjustmentTransactionForDualWrite($adjustment, $journalId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, false);

                    $adjustment->transaction()->associate($txn);

                    $adjustment->save();

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->trace->info(TraceCode::ADJUSTMENT_TRANSACTION_CREATED,
            [
                'transaction_id' => $txn->getId(),
                'source'         => 'dual_write_job'
            ]);

        return $txn;
    }

    private function pushToApiLedgerRetryTopic(array $payload)
    {
        $mode = $this->mode;

        $topic = $mode === 'live' ? env('LIVE_API_LEDGER_DUAL_WRITE_RETRY_TOPIC') : env('TEST_API_LEDGER_DUAL_WRITE_RETRY_TOPIC');

        try
        {
            $kafkaProducer = (new KafkaProducer($topic, stringify($payload)));

            $kafkaProducer->Produce();

            $this->trace->info(TraceCode::API_LEDGER_RETRY_PUSH_SUCCESS, [
                LedgerConstants::TOPIC        => $topic,
                LedgerConstants::MESSAGE      => $payload,
            ]);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::API_LEDGER_RETRY_PUSH_FAILURE,
                [
                    LedgerConstants::TOPIC        => $topic,
                    LedgerConstants::MESSAGE      => $payload,
                ]);
        }
    }

    private function createTransferAPILedger(array $journalResponse)
    {
        $this->trace->info(TraceCode::API_LEDGER_DUAL_WRITE_REQUEST_RECEIVED, [
            'transactor_event' => 'transfer_processed',
            'journal_response' => $journalResponse,
            'message' => 'message processing begin'
        ]);

        if(empty($journalResponse)===true || $journalResponse===null){

            $this->trace->count(Metric::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD,
                [
                    LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_PROCESSED,
                ]
            );

            $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                'transactor_event' => 'transfer_processed',
                'journal_response' => $journalResponse,
                'message' => 'invalid journal response'
            ]);

            return;
        }

        $transferId = $journalResponse['transfer_id'];

        $transferPaymentId =  $journalResponse['payment_id'];

        $bulkJournals = $journalResponse['journals'];

        if($transferPaymentId==='' || $transferId===''){

            $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                'transactor_event'    => 'transfer_processed',
                'journal_response'    => $journalResponse,
                'transfer_payment_id' => $transferPaymentId,
                'transfer_id'         => $transferId,
                'message'             => 'missing transfer or transfer payment id'
            ]);

            $this->trace->count(Metric::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD,
                [
                    LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_PROCESSED,
                ]
            );
            // hard failure, retry not needed.
            return ;
        }

        [$creditJournalId, $debitJournalId] = $this->determineJournalIdForAPITransaction($bulkJournals, "merchant_balance", "merchant_balance" );

        if((empty($creditJournalId)) or
           (empty($debitJournalId)))
        {
            $this->trace->count(Metric::API_LEDGER_DUAL_WRITE_EXPECTED_FUND_ACCOUNT_MISSING,
                [
                    LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_PROCESSED,
                ]
            );

            $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                'transactor_event'    => 'transfer_processed',
                'journal_response'    => $journalResponse,
                'transfer_payment_id' => $transferPaymentId,
                'transfer_id'         => $transferId,
                'debit_journal_id'    => $debitJournalId,
                'credit_journal_id'   => $creditJournalId,
                'message'             => 'missing transfer journal id or transfer payment journal id'
            ]);

            //no point in retrying as the message will never be consumed. Hard failure
            return;
        }

        $filteredDebitJournal = array_filter($bulkJournals, function ($item) use ($debitJournalId) {
            return $item['id'] === $debitJournalId;
        });

        $filteredCreditJournal = array_filter($bulkJournals, function ($item) use ($creditJournalId) {
            return $item['id'] === $creditJournalId;
        });

        $debitJournal = reset($filteredDebitJournal);

        $creditJournal = reset($filteredCreditJournal);

        $transfer = $this->repo->transfer->findOrFail($transferId);

        $transferPayment = $this->repo->payment->findOrFail($transferPaymentId);

        // mutex needed in case kafka re-balances, since same message can be picked again
        $resource='dual_write_ledger_transfer_processed_'.$transferId;

        $response= $this->mutex->acquireAndRelease(
            $resource,
            function () use ($debitJournal,$creditJournal,$transfer,$transferPayment)
            {
                 return $this->repo->transaction(function() use ($debitJournal,$creditJournal,$transfer,$transferPayment)
                {
                    $transferReverseShadowCore = new TransfersReverseShadowCore();

                    $debitTxn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($transfer);

                    $debitJournalId = $debitJournal[LedgerConstants::ID];

                    // if one txn is present, assumed other is present is too. Since txns are created inside db block.
                    if ((isset($debitTxn) === true) and
                        ($debitTxn->isBalanceUpdated() === true))
                    {
                        $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                            'transactor_event'    => 'transfer_processed',
                            'debit_journal_id'    => $debitJournalId,
                            'debit_txn'           => $debitTxn,
                            'message'             => 'txn already created for transfer skipping further steps'
                        ]);

                        return [
                            'transfer'=>$transfer,
                            'debit_journal_id'=>$debitJournalId,
                            'debit_txn'=>$debitTxn,
                            'message'=>'txn already created for transfer'
                        ] ;

                    }

                    if ((isset($debitTxn) === true) and
                        ($debitTxn->getId() !== $debitJournalId))
                    {
                        $journalId = $debitTxn->getId();

                        $this->trace->count(Metric::PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH,
                            [
                                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_PROCESSED,
                            ]
                        );

                        $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                            'transactor_event'    => 'transfer_processed',
                            'debit_journal_id'    => $debitJournalId,
                            'debit_txn'           => $debitTxn,
                            'message'             => 'txn id mismatch between cls and api txn found'
                        ]);
                    }

                    $creditJournalId = $creditJournal[LedgerConstants::ID];

                    $creditTxn = $this->repo->transaction->fetchBySourceAndAssociateMerchant($transferPayment);


                    if ((isset($creditTxn) === true) and
                        ($creditTxn->isBalanceUpdated() === true))
                    {
                        $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                            'transactor_event'    => 'transfer_processed',
                            'credit_journal_id'   => $creditJournalId,
                            'credit_txn'          => $creditTxn,
                            'message'             => 'txn already created for transfer_payment skipping further steps'
                        ]);

                        return [
                            'transfer_payment'   =>$transferPayment,
                            'credit_journal_id'  =>$creditJournalId,
                            'credit_txn'         =>$creditTxn,
                            'message'            =>'txn already created for transfer_payment'
                        ] ;

                    }

                    if ((isset($creditTxn) === true) and
                        ($creditTxn->getId() !== $creditJournalId))
                    {
                        $journalId = $creditTxn->getId();

                        $this->trace->count(Metric::PG_LEDGER_API_TRANSACTION_JOURNAL_ID_MISMATCH,
                            [
                                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::MERCHANT_CAPTURED,
                            ]
                        );

                        $this->trace->warning(TraceCode::API_LEDGER_DUAL_WRITE_INVALID_PAYLOAD, [
                            'transactor_event'    => 'transfer_processed',
                            'credit_journal_id'   => $creditJournalId,
                            'credit_txn'          => $creditTxn,
                            'message'             => 'txn id mismatch between cls and api txn found'
                        ]);

                        return [
                                 'transfer_payment'   =>$transferPayment,
                                 'credit_journal_id'  =>$creditJournalId,
                                 'credit_txn'         =>$creditTxn,
                                 'message'            =>'txn id mismatch between cls and api txn found'
                        ];
                    }

                    $txnCore = new Core();

                    $transferTxn = $transferReverseShadowCore->createTransferTransactionFromLedgerJournal($debitJournal, $transfer);

                    $transferPaymentTxn = $transferReverseShadowCore->createTransferPaymentTransactionFromLedgerJournalWithoutIdempotency($creditJournal, $transferPayment);

                    (new Transfer\Core())->updateBalanceAsyncForTransferTxn($transfer);

                    (new Transfer\Core())->updateBalanceAsyncForTransferPaymentTxn($transfer);

                    return [
                        'transfer_txn' => $transferTxn,
                        'transfer_payment_txn' => $transferPaymentTxn,
                        'message' =>'processing success'
                    ];
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->trace->info(TraceCode::API_LEDGER_DUAL_WRITE_REQUEST_PROCESSED, [
            'transactor_event'    => 'transfer_processed',
            'transfer_id'         => $transferId,
            'transfer_payment_id' => $transferPaymentId,
            'processing_response' => $response,
            'message'             => 'message processing finished'
        ]);

        $this->trace->count(Metric::API_LEDGER_DUAL_WRITE_EVENT_SUCCESS,
            [
                LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_PROCESSED,
            ]
        );
    }

    private function createTransferReversalAndRefundAPILedger(array $journalResponse)
    {
        $this->trace->info(TraceCode::API_LEDGER_DUAL_WRITE_REQUEST_RECEIVED, [
            'payload' => $journalResponse,
        ]);

        $reversalAndRefundJournalIds = $journalResponse['reversal_and_refund_journal_ids'];

        $journals = $journalResponse['journals'];

        $reversals = $reversalAndRefundJournalIds["reversals"];

        $customerRefundId =  $reversalAndRefundJournalIds["customer_refund_id"] ?? "";

        foreach ($reversals as $item)
        {
            $reversalId             = $item["transfer_reversal_id"];
            $reversalJournalId      = $item["transfer_reversal_journal_id"];
            $dummyRefundId          = $item["refund_id"];
            $dummyRefundJournalId   = $item["refund_journal_id"];

            $reversal = $this->repo->reversal->findOrFail($reversalId);

            $filteredReversalJournal = array_filter($journals, function ($item) use ($reversalJournalId)
            {
                return $item['id'] === $reversalJournalId;
            });

            $reversalJournal = reset($filteredReversalJournal);

            if (empty($reversalJournal) === false)
            {
                $this->createTransferReversalAPILedger($reversalJournal);
            }

            $filteredRefundJournal = array_filter($journals, function ($item) use ($dummyRefundJournalId)
            {
                return $item['id'] === $dummyRefundJournalId;
            });

            $dummyRefundJournal = reset($filteredRefundJournal);

            if (empty($dummyRefundJournal) === false)
            {
                $this->createTransferPaymentRefundAPILedger($dummyRefundId, $dummyRefundJournal);
            }
        }

        if($customerRefundId !== "")
        {
            $customerRefundJournalId = $reversalAndRefundJournalIds["customer_refund_journal_id"];

            $filteredCustomerRefundJournal = array_filter($journals, function ($item) use ($customerRefundJournalId)
            {
                return $item['id'] === $customerRefundJournalId;
            });

            $customerRefundJournal = reset($filteredCustomerRefundJournal);

            if (empty($customerRefundJournal) === false)
            {
                $this->createRefundAPILedger($customerRefundJournal);
            }
        }

        $this->trace->count(Metric::API_LEDGER_DUAL_WRITE_EVENT_SUCCESS, [
            LedgerConstants::TRANSACTOR_EVENT => LedgerConstants::TRANSFER_REVERSAL_PROCESSED,
        ]);

        $this->trace->info(TraceCode::API_LEDGER_DUAL_WRITE_REQUEST_PROCESSED, [
            'reversal_and_refund_journal_ids'         => $reversalAndRefundJournalIds,
        ]);
    }

    private function createTransferReversalAPILedger(array $journalResponse)
    {
        $transactorPublicId = $journalResponse[LedgerConstants::TRANSACTOR_ID];

        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $transactorInfo = $this->determineTransactionTypeFromTransactorId($transactorPublicId);

        $reversalId =  $transactorInfo[LedgerConstants::ID];

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $reversalId.'_transaction',
            function () use ($reversalId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
            {
                return $this->repo->transaction(function() use ($reversalId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
                {
                    $txn = $this->repo->transaction->findByEntityId($reversalId, $merchant);

                    if($txn !== null)
                    {
                        $this->trace->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_EXISTS, $journalResponse);

                        return $txn;
                    }

                    $reversal = $this->repo->reversal->findOrFail($reversalId);

                    $txnCore = new Core();

                    list($txn, $feesSplit) = $txnCore->createTransactionForSourceDualWrite($reversal, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::TRANSFER_REVERSAL_TRANSACTION_CREATE_SUCCESS, [
            'id'          => $txn->getId(),
            'reversal_id' => $reversalId,
        ]);
    }

    private function createTransferPaymentRefundAPILedger($refundId, $journalResponse)
    {
        [$merchantId, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed] = $this->getMerchantIdCreditsAndPricingInfoFromJournalResponse($journalResponse);

        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $journalId = $journalResponse['id'];

        $txn = $this->mutex->acquireAndRelease(
            $refundId.'_transaction',
            function () use ($refundId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
            {
                return $this->repo->transaction(function() use ($refundId, $journalId, $merchant,  $journalResponse, $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed)
                {

                    $txn = $this->repo->transaction->findByEntityId($refundId, $merchant);

                    if($txn !== null)
                    {
                        $this->trace->info(TraceCode::DUMMY_PAYMENT_REFUND_TRANSACTION_EXISTS, $journalResponse);
                    }

                    $refund = $this->repo->refund->findOrFail($refundId);

                    $txnCore = new Core();

                    list($txn, $feesSplit) = $txnCore->createTransactionForSourceDualWrite($refund, $journalId,  $fees, $tax, $feeCreditUsed, $amountCreditUsed, $refundCreditUsed);

                    return $txn;
                });
            },
            self::ENTITY_TRANSACTION_CREATION_MUTEX_TTL,
            ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_RETRIES,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MIN_RETRY_DELAY,
            self::ENTITY_TRANSACTION_CREATION_MUTEX_MAX_RETRY_DELAY
        );

        $this->repo->saveOrFail($txn);

        $this->trace->info(TraceCode::DUMMY_PAYMENT_REFUND_TRANSACTION_CREATE_SUCCESS, [
            'id'         => $txn->getId(),
            'refund_id'  => $refundId,
        ]);
    }

}
