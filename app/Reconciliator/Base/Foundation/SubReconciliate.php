<?php

namespace RZP\Reconciliator\Base\Foundation;

use App;
use Carbon\Carbon;
use RZP\Constants\Entity as EntityConstants;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Terminal;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use Razorpay\Trace\Logger;
use RZP\Reconciliator\Core;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Messenger;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Reconciliator\Orchestrator;
use Razorpay\Trace\Logger as Trace;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Base\Constants;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use RZP\Jobs\CardMetaDataDelete as CardMetaDataDeleteJob;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class SubReconciliate extends Base\Core
{
    use FileHandlerTrait;

    const TOTAL_SUMMARY     = 'total_summary';
    const FAILURES_SUMMARY  = 'failures_summary';
    const SUCCESSES_SUMMARY = 'successes_summary';

    // used in recon processing output file
    const RECON_TYPE            = 'recon_type';
    const RECON_STATUS          = 'recon_status';
    const ALREADY_RECONCILED_AT = 'already_reconciled_at';
    const RECON_ERROR_MSG       = 'recon_error_msg';
    const RZP_MERCHANT_ID       = 'rzp_merchant_id';
    const PROCESSED_AT          = 'processed_at';
    const BATCH_ID              = 'batch_id';
    const ATTEMPT_NUMBER        = 'attempt_number';
    const RECON_ENTITY_ID       = 'recon_entity_id';
    const RECON_NET_AMOUNT      = 'recon_net_amount';

    // Txn file related specific fields
    const RZP_TXN_ID            = 'rzp_txn_id';
    const RZP_TXN_AMOUNT        = 'rzp_txn_amount';
    const RZP_TXN_CURRENCY      = 'rzp_txn_currency';
    const RZP_GATEWAY           = 'rzp_gateway';
    const RZP_GATEWAY_ACQUIRER  = 'rzp_gateway_acquirer';
    const RZP_IS_RECONCILED     = 'rzp_is_reconciled';
    const RZP_RECONCILED_AT     = 'rzp_reconciled_at';
    const RZP_IS_RECONCILIABLE  = 'rzp_is_reconciliable';
    const RZP_TXN_CREATED_AT    = 'rzp_txn_created_at';
    const RZP_TERMINAL_ID       = 'rzp_terminal_id';
    const RZP_SETTLED_BY        = 'rzp_settled_by';
    const RZP_METHOD            = 'rzp_method';
    const TAG_1                 = 'tag_1';
    const TAG_2                 = 'tag_2';
    const TAG_3                 = 'tag_3';
    const TEMP_VAULT_TOKEN_PREFIX = 'pay_';

    const TXN_FILE_ADDITIONAL_FIELDS = [
        self::RZP_TXN_ID,
        self::RZP_TXN_AMOUNT,
        self::RZP_TXN_CURRENCY,
        self::RZP_GATEWAY,
        self::RZP_GATEWAY_ACQUIRER,
        self::RZP_IS_RECONCILED,
        self::RZP_RECONCILED_AT,
        self::RZP_IS_RECONCILIABLE,
        self::RZP_TXN_CREATED_AT,
        self::RZP_TERMINAL_ID,
        self::RZP_SETTLED_BY,
        self::RZP_METHOD,
    ];

    // For these methods, there can be multiple acquirers for the same gateway
    const METHODS_WITH_MULTIPLE_ACQUIRERS = [
        Payment\Method::CARD,
        Payment\Method::EMI,
        Payment\Method::PAYLATER,
        Payment\Method::CARDLESS_EMI,
    ];

    const THRESHOLD = [
        InfoCode::AMOUNT_MISMATCH   =>  10,
    ];

    const KEY_PREFIX            = 'recon_';
    const MUTEX_LOCK_TIMEOUT    = 60; //seconds

    /**
     * The list of payments/refunds attempted to reconcile.
     *
     * @var array
     */
    protected $total = [];

    /**
     * All the payments/refunds which were successfully reconciled.
     * These include payments/refunds for which we were able to successfully record the gateway
     * service tax and gateway fees in db.
     *
     * @var array
     */
    protected $successes = [];

    /**
     * All the payments/refunds which could not be reconciled.
     * These include the payments/refunds for which we could not record the gateway service tax
     * and gateway fees in db.
     *
     * @var array
     */
    protected $failures = [];

    /**
     * All the rows for which we could not decide the recon type
     * and thus skipped from processing.
     *
     * @var array
     */
    protected $skippedRows = [];

    /**
     * Decides whether to mark the row as success / failure if it is unprocessable.
     * By default, we want to mark such a row as failed, hence setting it to true.
     *
     * @var boolean
     */
    protected $failUnprocessedRow = true;

    protected $gateway;

    /**
     * @var array array list of refund and corresponding data that
     * will be dispatched to scrooge for recon processing
     */
    protected static $scroogeReconciliate = [];

    /**
     * Indicates whether the Recon file uploaded via mailgun or manual
     */
    protected $source;

    protected $core;

    protected $messenger;

    protected $batch;

    /**
     * In case of recon request via Batch service, we do not have
     * the batch object, so we use this variable for batch_id in logs.
     * @var mixed|null
     */
    protected $batchId;

    /**
     * @var array This array will contain MIS row and
     * corresponding reconciliation status and error
     * msg if any. later an output file will be created.
     */
    protected static $reconOutputData = [];

    protected static $currentRowNumber = -1;

    public function __construct(string $gateway = null, Batch\Entity $batch = null)
    {
        parent::__construct();

        $this->gateway = $gateway;

        $this->batch = $batch;

        $this->mutex = $this->app['api.mutex'];

        $this->batchId = $batch ? $batch->getId() : null;

        $this->core = new Core;

        $this->messenger = new Messenger;
    }

    public function getTotal(): array
    {
        return $this->total;
    }

    public function getSuccesses(): array
    {
        return $this->successes;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Contains details for files, email or manual details
     * Manual details is being used to check for force_update and force_authorize payments
     *
     * @var array
     */
    protected $extraDetails = [];

    /**
     * This method resets any instance attributes which could have been set during
     * processing reconciliation of a particular row. In certain cases like combined
     * reconciliate, the subreconciliator instances are reused so we don't want
     * instance attributes to persist between specific runs. Implementation to be
     * provided by child classes
     */
    public function resetRowProcessingAttributes()
    {
        $this->setFailUnprocessedRow(true);
    }

    /**
     * This is the start of the actual reconciliation.
     * Reconciliation is done for each row in the file content.
     *
     * @param array $fileContents
     * @return array
     */
    public function startReconciliation(array $fileContents)
    {
        $this->setExtraDetails($fileContents[Orchestrator::EXTRA_DETAILS]);
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        foreach ($fileContents as $row)
        {
            $this->repo->transactionOnLiveAndTest(function() use ($row)
            {
                $this->runReconciliate($row);
            });
        }

        return $this->getSummary();
    }

    /**
     * Runs the same reconciliation process, though here we always update the batch with recon
     * summary, regardless of any exception thrown during the process.
     *
     * @param array $fileContents file contents to be processed
     * @param Batch\Processor\Base $batchProcessor
     * @throws \Throwable
     */
    public function startReconciliationV2(array $fileContents, Batch\Processor\Base $batchProcessor)
    {
        $batch = $batchProcessor->batch;

        $this->batchId = $batch ? $batch->getId() : null;

        $this->setExtraDetails($fileContents[Orchestrator::EXTRA_DETAILS]);
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

        if (empty($this->batchId) === true)
        {
            $this->batchId = $this->extraDetails[Batch\Entity::CONFIG][Constants::BATCH_ID] ?? null;
        }

        $this->messenger->batchId = $this->batchId;

        $this->preProcess($fileContents);

        try
        {
            foreach ($fileContents as $row)
            {
                try
                {
                    $this->repo->transactionOnLiveAndTest(function () use ($row) {
                        $this->runReconciliate($row);
                    });
                }
                catch (\Exception $ex)
                {
                    //
                    // Making identifier as empty string if it is null as
                    // setSummaryCount expects string identifier.
                    //
                    $identifier = head($row) ?? '';

                    $this->setSummaryCount(self::FAILURES_SUMMARY, $identifier);

                    if (empty($this->extraDetails[Batch\Processor\Reconciliation::BATCH_SERVICE_RECON_REQUEST]) === true)
                    {
                        //
                        // We don't want to terminate the batch processing
                        // when the transaction creation failed for a single payment
                        //
                        if ((empty(static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG]) === true) or
                            ((empty(static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG] === false) and
                                (static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG] !== InfoCode::RECON_RECORD_GATEWAY_FEE_TRANSACTION_ABSENT))))
                        {
                            throw $ex;
                        }
                    }

                    //
                    // For Recon request coming from batch service,
                    // we don't want to terminate batch processing,
                    // so just trace the exception and proceed to next row
                    //
                    $tracePayload = [
                        'gateway'   => $this->gateway,
                        'batch_id'  => $this->batchId,
                    ];

                    $this->trace->traceException($ex, Logger::ALERT, TraceCode::RECON_ROW_PROCESSING_ERROR, $tracePayload);
                }
                finally
                {
                    if ($batch !== null)
                    {
                        $batch->incrementProcessedCount();
                    }
                }
            }
        }
        finally
        {
            //
            // setting the variable null here to free up the memory associated with this variable.
            // not calling unset as that only removes the reference and the GC will free up the memory.
            //
            $fileContents = null;

            $this->setReconOutputData($batchProcessor);

            if (count(static::$scroogeReconciliate) > 0)
            {
                $forceUpdateArn = $this->shouldForceUpdate(RequestProcessor\Base::REFUND_ARN);

                $batchProcessor->setScroogeDispatchData(
                    [
                        'data'              => static::$scroogeReconciliate,
                        'source'            => $this->source,
                        'force_update_arn'  => $forceUpdateArn,
                        'gateway'           => $this->gateway,
                        'batch_id'          => $this->batchId,
                    ]
                );

                //
                // Need to reset it now, else few testcases are failing when we run
                // ReconciliationFileTest. Though individually the same test passes.
                // (even the payment recon test, having only payment rows in MIS file
                // also have this scroogeReconciliate data set and thus scrooge dispatch happened)
                //
                static::$scroogeReconciliate = [];
            }

            if ($batch !== null)
            {
                $this->updateBatchWithSummary($batch);
            }
        }
    }

    protected function preProcess(array & $rows)
    {
        return null;
    }

    /**
     * Add the recon row with initial data available
     * @param $row
     * @param $reconType
     */
    protected function insertRowInOutputFile(array $row = [], string $reconType = 'unknown')
    {
        $processed_at = Carbon::now(Timezone::IST)->format('Y-m-d H:i:s');

        $row[self::RECON_TYPE]              = $reconType;
        $row[self::RECON_STATUS]            = '';
        $row[self::ALREADY_RECONCILED_AT]   = '';
        $row[self::RECON_ERROR_MSG]         = null;
        $row[self::RZP_MERCHANT_ID]         = '';
        $row[self::PROCESSED_AT]            = $processed_at;
        $row[self::BATCH_ID]                = '';
        $row[self::ATTEMPT_NUMBER]          = '';
        $row[self::RECON_ENTITY_ID]         = '';
        $row[self::RECON_NET_AMOUNT]        = '';

        // Txn file related additional fields
        $row[self::RZP_TXN_ID]              = '';
        $row[self::RZP_TXN_AMOUNT]          = '';
        $row[self::RZP_TXN_CURRENCY]        = '';
        $row[self::RZP_GATEWAY]             = '';
        $row[self::RZP_GATEWAY_ACQUIRER]    = '';
        $row[self::RZP_IS_RECONCILED]       = '';
        $row[self::RZP_RECONCILED_AT]       = '';
        $row[self::RZP_IS_RECONCILIABLE]    = 1;
        $row[self::RZP_TXN_CREATED_AT]      = '';
        $row[self::RZP_TERMINAL_ID]         = '';
        $row[self::RZP_SETTLED_BY]          = '';
        $row[self::RZP_METHOD]              = '';

        static::$reconOutputData[] = $row;

        static::$currentRowNumber += 1;
    }

    protected function setReconOutputData(Batch\Processor\Base $batchProcessor)
    {
        $batchProcessor->setReconBatchOutputData(static::$reconOutputData);

        //
        // Note : resetting is mandatory when multiple
        // files are uploaded for recon together
        //
        static::$reconOutputData = [];
        static::$currentRowNumber = -1;
    }

    protected function persistReconciledAt($entity, string $reconciledType = ReconciledType::MIS)
    {
        if (($entity->getEntityName() !== Entity::REFUND) or
            ($entity->isScrooge() === false))
        {
            $transaction = $entity->transaction;

            $time = time();
            $transaction->setReconciledAt($time);
            $transaction->setReconciledType($reconciledType);

            $transaction->saveOrFail();

            $this->deleteCardMetaDataIfApplicable($entity);

            $this->pushSuccessReconMetrics($entity);

            // Increment the success count for the summary.
            $this->setSummaryCount(self::SUCCESSES_SUMMARY, $entity->getKey());
        }

        $this->setRowReconStatusAndError(InfoCode::RECONCILED);
    }

    /**
     * This function pushes metrics for a payment/refund,
     * when it get marked reconciled
     *
     * @param $entity
     */
    protected function pushSuccessReconMetrics($entity)
    {
        $entityName = $entity->getEntityName();

        switch($entityName)
        {
            case Entity::PAYMENT:
                $this->core->pushSuccessPaymentReconMetrics($entity, $this->source);

                break;
            case Entity::REFUND:
                $this->core->pushSuccessRefundReconMetrics($entity, $this->source);

                break;
            default:
                $this->trace->error(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'message'            => 'To push this metric, entity must be refund or payment only',
                        'entity_id'          => $entity->getId(),
                        'entity_name'        => $entity->getEntityName(),
                    ]);
        }
    }

    /**
     * Add mutex lock for given resource Id
     * parameter required for lock in following order(acquire function)
     * $resourceWithPrefix - as key for lock
     * MUTEX_LOCK_TIMEOUT - time in seconds for lock release
     * number of times the lock should try
     * Minimum time to wait before retry in millisec
     * Maximum time to wait before retry in millisec
     *
     * @param $resourceId
     */
    protected function lockResourceForRecon($resourceId)
    {
        $resourceWithPrefix = self::KEY_PREFIX . $resourceId;

        $this->mutex->acquire($resourceWithPrefix, self::MUTEX_LOCK_TIMEOUT, 20 , 100 , 200);
    }

    /**
     * release mutex lock for given resource Id
     * @param $resourceId
     */
    protected function releaseResourceForRecon($resourceId)
    {
        $resourceWithPrefix = self::KEY_PREFIX . $resourceId;

        $this->mutex->release($resourceWithPrefix);
    }

    protected function persistGatewaySettledAt(Base\Entity $entity, array $rowDetails)
    {
        $gatewaySettledAt = $rowDetails[BaseReconciliate::GATEWAY_SETTLED_AT];

        if (empty($gatewaySettledAt) === true)
        {
            return;
        }

        $transaction = $entity->transaction;

        // Since we might be running this before the actual recon process,
        // it's possible that the transaction for this particular entity is
        // not even present.
        // We will be running this in the last step of recon process too.
        // So, it will get recorded in that step, if not in the first step.
        if ($transaction === null)
        {
            return;
        }

        // If this is already recorded, no need to record it again.
        // This could have got recorded in pre-recon-process itself.
        if ($transaction->getGatewaySettledAt() !== null)
        {
            return;
        }

        if (($entity->getEntityName() === Entity::REFUND) and
            ($entity->isScrooge() === true))
        {
            static::$scroogeReconciliate[$entity->getId()]->setGatewaySettledAt($gatewaySettledAt);
        }
        else
        {
            $transaction->setGatewaySettledAt($gatewaySettledAt);

            $this->repo->saveOrFail($transaction);
        }
    }

    protected function persistGatewayAmount(Base\Entity $entity, array $rowDetails)
    {
        $gatewayAmount = $rowDetails[BaseReconciliate::GATEWAY_AMOUNT];

        $transaction = $entity->transaction;

        if (($gatewayAmount === null) or
            ($transaction === null) or
            ($transaction->getGatewayAmount() !== null))
        {
            return;
        }

        $transaction->setGatewayAmount($gatewayAmount);

        $this->repo->saveOrFail($transaction);
    }

    protected function checkIfAlreadyReconciled($entity)
    {
        $transaction = $entity->transaction;

        if ($transaction === null)
        {
            // If transaction is not present, it would mean that
            // the reconciliation did not happen for this.
            return false;
        }

        return $entity->transaction->isReconciled();
    }

    protected function setSummaryCount(string $type, string $identifier)
    {
        switch($type)
        {
            case self::TOTAL_SUMMARY:
                $this->total[] = $identifier;
                break;
            case self::FAILURES_SUMMARY:
                $this->failures[] = $identifier;
                break;
            case self::SUCCESSES_SUMMARY:
                $this->successes[] = $identifier;
                break;
            default:
                throw new LogicException(
                    'Should not have reached here. Unknown type given for summary.',
                    null,
                    ['entity_id' => $identifier]
                );
        }
    }

    protected function deleteCardMetaDataIfApplicable($entity)
    {
        try {

            if($entity->getEntityName() !== EntityConstants::PAYMENT)
            {
                return;
            }
            // (str_contains($card->getVaultToken(), self::TEMP_VAULT_KMS_TOKEN_PREFIX) === true) add for pay_2 token after it is live\
            if (($entity->isMethodCardOrEmi() === true) and
                (str_contains($entity->card->getVaultToken(), self::TEMP_VAULT_TOKEN_PREFIX) === true)) {

                if($entity->getGateway() ===  Payment\Gateway::PAYSECURE or $entity->getGateway() ===  Payment\Gateway::FULCRUM )
                {
                    $variant = $this->app['razorx']->getTreatment($entity->getId(), RazorxTreatment::DELETE_CARD_METADATA_AFTER_RECONCILIATION_FOR_PAYSECURE_AND_FULCRUM, $this->app['rzp.mode'] ?? 'live');
                }
                else
                {
                    $variant = $this->app['razorx']->getTreatment($entity->getId(), RazorxTreatment::DELETE_CARD_METADATA_AFTER_RECONCILIATION, $this->app['rzp.mode'] ?? 'live');
                }

                $this->trace->info(
                    TraceCode::DELETING_METADATA_AFTER_RECONCILIATION,
                    [
                        'id'          => $entity->getId(),
                        'vault_token' => $entity->card->getvaultToken(),
                        'gateway'     => $entity->getGateway(),
                        'variant'     => $variant
                    ]);

                if ($variant === 'on' ) {
                    $data = [
                        'mode'        => $this->app['rzp.mode'] ?? 'live',
                        'payment_id'  => $entity->getId(),
                        'vault_token' => $entity->card->getVaultToken()
                    ];

                    CardMetaDataDeleteJob::dispatch($data);
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->info(
                TraceCode::CARD_METADATA_DELETE_EXCEPTION,
                [
                    'id'          => $entity->getId()
                ]);
        }

    }

    protected function getSummary()
    {
        $summary = [
            'total_count' => count($this->total),
            'failure_count' => count($this->failures),
            'success_count' => count($this->successes),
        ];

        if (empty($this->failures) === false)
        {
            $summary['failures'] = $this->failures;
        }

        return $summary;
    }

    /**
     * To be overridden in child class
     * @param array $rowDetails
     * @param PublicEntity $gatewayPayment
     */
    protected function persistGatewayUtr(array $rowDetails, PublicEntity $gatewayPayment)
    {
        return;
    }

    /**
     * Being used in Worldline gateway (VasAxis) only.
     * If present, will be saved in worldline entity
     */
    protected function getGatewayUtr($row)
    {
        return null;
    }

    /**
     * Not all gateways provide us with gateway_settled_at.
     * Hence, we send back null for these gateways.
     *
     * @param $row
     * @return null
     */
    protected function getGatewaySettledAt(array $row)
    {
        return null;
    }

    /**
     * Not all gateways provide us with gateway_amount.
     * Hence, we send back null for these gateways.
     *
     * @param $row
     * @return null
     */
    protected function getGatewayAmount(array $row)
    {
        return null;
    }

    /**
     * This is a uniqueId generated for gateway. It is similar to paymentId. This is used in netbanking kotak gateway.
     * This created as bank wants uniqueId to be numeric with randoms.
     *
     * @param $row
     * @return null
     */
    protected function getGatewayUniqueId(array $row)
    {
        return null;
    }

    /**
     * This is a uniqueId generated by gateway. It is similar to paymentId. This is generated in case of DC EMI as loan
     * booking ref id
     *
     * @param $row
     * @return null
     */
    protected function getGatewayReferenceId1(array $row)
    {
        return null;
    }


    /**
     * This is a uniqueId generated by gateway. It is similar to paymentId. This is generated in response of capture call
     *
     * @param $row
     * @return null
     */
    protected function getGatewayReferenceId2(array $row)
    {
        return null;
    }

    /**
     * Method check if FORCE_UPDATE for argument fields
     * is passed in MANUAL_DETAILS.
     *
     * @param string $field
     * @return bool
     */
    protected function shouldForceUpdate(string $field) : bool
    {
        $forceUpdateFields = $this->extraDetails
            [RequestProcessor\Base::INPUT_DETAILS]
            [RequestProcessor\Base::FORCE_UPDATE] ?? [];

        return in_array($field, $forceUpdateFields, true);
    }

    public function setExtraDetails(array $extraDetails)
    {
        $this->extraDetails = $extraDetails;
    }

    /**
     * @param  Batch\Entity $batch  Batch entity for the current reconciliation request
     */
    protected function updateBatchWithSummary(Batch\Entity $batch)
    {
        //
        // We are not updating the batch total count here, as that is already done
        // when we parse the file, before processing has begn. This is because recon
        // files usually have extra rows, and hence updating the total_count here
        // will not reflect the actual number of rows in the file.
        //
        // Getting previous success and failure count if set, as in case of multiple sheets
        // $this->successes contains only current sheet's success rows

        $successes = $batch->getSuccessCount();

        $failures = $batch->getFailureCount();

        $batch->setSuccessCount($successes + count($this->successes));

        $batch->setFailureCount($failures + count($this->failures));
    }

    /**
     * Rows for which the corresponding entities, have already been marked as reconciled,
     * we add it to the list of successfully processed rows.
     *
     * @param string $entityId
     * @param int $reconciledAt
     */
    protected function handleAlreadyReconciled(string $entityId, int $reconciledAt = null)
    {
        $this->setRowReconStatusAndError(InfoCode::ALREADY_RECONCILED, null, $reconciledAt);

        $this->setSummaryCount(self::SUCCESSES_SUMMARY, $entityId);
    }

    protected function handlePersistReconciliationDataFailure(string $entityId)
    {
        // Increment the failure count for the summary.
        $this->setSummaryCount(self::FAILURES_SUMMARY, $entityId);
    }


    protected function setFailUnprocessedRow(bool $failUnprocessedRow)
    {
        $this->failUnprocessedRow = $failUnprocessedRow;
    }

    public function setSource(string $source)
    {
        $this->source = $source;
    }

    /**
     * Sets the status, error msg, already reconciled_at time
     * for the current row in progress
     *
     * @param string $status
     * @param string|null $errorCode
     * @param int|null $reconciledAt
     */
    protected function setRowReconStatusAndError(string $status, string $errorCode = null, int $reconciledAt = null)
    {
        static::$reconOutputData[static::$currentRowNumber][self::RECON_STATUS] = $status;

        if ($status === InfoCode::ALREADY_RECONCILED)
        {
            // Add the already reconciled_at time
            $reconciledTime = Carbon::createFromTimestamp($reconciledAt, Timezone::IST)->format('Y-m-d H:i:s');

            static::$reconOutputData[static::$currentRowNumber][self::ALREADY_RECONCILED_AT] = $reconciledTime;
        }

        //
        // For error codes, we don't want to overwrite it, because the first point
        // where we set the error code, that is very specific to the issue.
        //
        $existingErrorCode = static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG];

        if ((empty($errorCode) === false) and
            (empty($existingErrorCode) === true))
        {
            static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG] = $errorCode;
        }
    }

    protected function setTransactionDetailsInOutput($transaction)
    {
        if ($transaction === null)
        {
            return;
        }

        static::$reconOutputData[static::$currentRowNumber][self::RZP_TXN_ID]           = $transaction->getId();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_IS_RECONCILED]    = $this->getReconciledStatus();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_RECONCILED_AT]    = $this->getReconTimestamp();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_TXN_CREATED_AT]   = $transaction->getCreatedAt();
    }

    // Sets terminal ID, settled by and Method for the entity (Payment/Refund)
    protected function setMiscEntityDetailsInOutput(PublicEntity $entity)
    {
        static::$reconOutputData[static::$currentRowNumber][self::RECON_ENTITY_ID]      = $entity->getId();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_TXN_AMOUNT]       = $entity->getAmount();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_TXN_CURRENCY]     = $entity->getCurrency();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_MERCHANT_ID]      = $entity->getMerchantId();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_GATEWAY]          = $entity->getGateway();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_SETTLED_BY]       = $entity->getSettledBy();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_METHOD]           = $entity->getMethod();
    }

    protected function setTerminalDetailsInOutput($terminal)
    {
        if ($terminal === null)
        {
            return;
        }

        // By this time, method and gateway is set in the output row, just use that
        $method  = static::$reconOutputData[static::$currentRowNumber][self::RZP_METHOD];
        $gateway = static::$reconOutputData[static::$currentRowNumber][self::RZP_GATEWAY];

        // Check if we should use gateway or gateway acquirer
        // e.g for card or emi methods, there can be many acquirers for the same gateway.
        if (in_array($method, self::METHODS_WITH_MULTIPLE_ACQUIRERS, true) === true)
        {
            $gatewayAcquirer = $terminal->getGatewayAcquirer();
        }
        else
        {
            $gatewayAcquirer = $gateway;
        }

        static::$reconOutputData[static::$currentRowNumber][self::RZP_TERMINAL_ID]       = $terminal->getId();
        static::$reconOutputData[static::$currentRowNumber][self::RZP_GATEWAY_ACQUIRER]  = $gatewayAcquirer;
    }

    protected function setBatchIdInOutput($batchId)
    {
        static::$reconOutputData[static::$currentRowNumber][self::BATCH_ID] = $batchId;
    }

    protected function setAttemptsInOutput($attemptNumber)
    {
        static::$reconOutputData[static::$currentRowNumber][self::ATTEMPT_NUMBER] = $attemptNumber;
    }

    protected function setReconNetAmountInOutput(float $reconNetAmount)
    {
        static::$reconOutputData[static::$currentRowNumber][self::RECON_NET_AMOUNT] = $reconNetAmount;
    }

    /**
     * Returns 1 if the row has been reconciled, else return 0
     *
     * Since we can't use transactions->getReconciledAt() for refund entity,
     * as refund's txn get marked reconciled after scrooge response.
     * so we get reconciled status from the output array.
     *
     * @return int
     */
    protected function getReconciledStatus()
    {
        $reconStatus = static::$reconOutputData[static::$currentRowNumber][self::RECON_STATUS];

        if (($reconStatus === InfoCode::RECONCILED) or
            ($reconStatus === InfoCode::ALREADY_RECONCILED))
        {
            return 1;
        }

        return 0;
    }

    /**
     * Returns processed_at time if the row has been reconciled.
     *
     * @return string
     */
    protected function getReconTimestamp()
    {
        if ($this->getReconciledStatus() === 0)
        {
            return null;
        }
        else
        {
            $processedAt = static::$reconOutputData[static::$currentRowNumber][self::PROCESSED_AT];

            return Carbon::createFromFormat('Y-m-d H:i:s', $processedAt, Timezone::IST)->timestamp;
        }
    }

    /**
     * For certain rows, where we are not able to successfully identify the payment
     * or refund entity to reconcile, we mark the row processing as success or failure
     * depending on the specific gateway's reconciliator.
     *
     * @param array $row
     * @throws LogicException
     */
    protected function handleUnprocessedRow(array $row)
    {
        $rowStatus = ($this->failUnprocessedRow === true) ? 'Failed' : 'Success';

        $reconRowForTrace = $this->removePiiColumns($row);

        $this->trace->info(TraceCode::RECON_UNPROCESSED_ROW,
            [
                'gateway' => $this->gateway,
                'status'  => $rowStatus,
                'row'     => $reconRowForTrace,
            ]);

        //
        // Making identifier as empty string if it is null as setSummaryCount expects string identifier.
        //
        $identifier = head($row) ?? '';

        $this->setSummaryCount(self::TOTAL_SUMMARY, $identifier);

        if ($this->failUnprocessedRow === true)
        {
            $this->setSummaryCount(self::FAILURES_SUMMARY, $identifier);
        }
        else
        {
            // update the recon status in the output file
            $this->setRowReconStatusAndError(InfoCode::RECON_UNPROCESSED_SUCCESS);

            $this->setSummaryCount(self::SUCCESSES_SUMMARY, $identifier);
        }
    }

    protected function handleFailedValidation(string $entityId)
    {
        // Increment the failure count for the summary.
        $this->setSummaryCount(self::FAILURES_SUMMARY, $entityId);
    }

    /**
     * Remove PII columns and trace recon row
     *
     * @param array $row
     */
    protected function traceReconRow(array $row)
    {
        $reconRowForTrace = $this->removePiiColumns($row);

        $this->trace->info(
            TraceCode::RECON_FILE_ROW,
            $reconRowForTrace
        );
    }

    /**
     * Get the PII columns for gateway and
     * return array after un-setting.
     *
     * @param array $row
     * @return array
     */
    protected function removePiiColumns(array $row)
    {
        $piiColumns = $this->getPiiColumnHeadersForLogs();

        foreach ($piiColumns as $piiColumn)
        {
            if (isset($row[$piiColumn]) === true)
            {
                unset($row[$piiColumn]);
            }
        }

        return $row;
    }

    /**
     * The method formulates gateway name using terminal's gateway and
     * gateway acquirer. This is required to check if there is mismatch
     * between the recon gateway and the payment gateway.
     *
     * @param PaymentEntity $payment
     * @return string
     */
    protected function getGatewayNameFromPayment(Payment\Entity $payment)
    {
        $paymentGateway = null;

        //
        // In case of CardFssHdfc, the payment's gateway is set as 'card_fss'
        // so we need to get the acquirer from the terminal so as to construct
        // the expected recon gateway as 'CardFssHdfc'
        //

        $terminal = $this->repo->terminal->fetchForPayment($payment);

        $terminalGateway = $terminal->getGateway();

        if (isset(RequestProcessor\Base::GATEWAY_NAME_MAPPING[$terminalGateway]) === false)
        {
            $paymentGateway = $payment->getGateway();

            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'         => InfoCode::UNEXPECTED_TERMINAL_GATEWAY,
                    'payment_id'        => $payment->getId(),
                    'payment_gateway'   => $paymentGateway,
                    'terminal_id'       => $terminal->getId(),
                    'terminal_gateway'  => $terminalGateway,
                    'gateway'           => $this->gateway,
                ]
            );

            return $paymentGateway;
        }

        $mappedGateway = RequestProcessor\Base::GATEWAY_NAME_MAPPING[$terminalGateway];

        if (is_array($mappedGateway) === false)
        {
            $paymentGateway = $mappedGateway;
        }
        else
        {
            //
            // This happens when the terminal gateway is cybersource or card_fss.
            // We need to check the acquirer to formulate the recon gateway name
            //
            // i.e. for card_fss, we formulate recon gateway as CardFssBob or
            // CardFssHdfc, depending on whether the acquirer is barb or hdfc.
            //
            $gatewayAcquirer = $terminal->getGatewayAcquirer();

            if (isset($mappedGateway[$gatewayAcquirer]) === false)
            {
                $this->trace->info(
                    TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code'                     => InfoCode::UNEXPECTED_TERMINAL_GATEWAY_ACQUIRER,
                        'payment_id'                    => $payment->getId(),
                        'terminal_id'                   => $terminal->getId(),
                        'gateway_acquirer'              => $gatewayAcquirer,
                        'expected_gateway_acquirers'    => array_keys($mappedGateway),
                        'gateway'                       => $this->gateway,
                    ]
                );
            }
            else
            {
                $paymentGateway = $mappedGateway[$gatewayAcquirer];
            }
        }

        return $paymentGateway;
    }

    /**
     * @param array $row
     * @param string $columnName
     */
    protected function reportMissingColumn(array $row, string $columnName)
    {
        $this->trace->info(
            TraceCode::RECON_INFO_ALERT,
            [
                'message'           => 'Unable to get the expected column.',
                'column_name'       => $columnName,
                'gateway'           => $this->gateway
            ]);
    }

    /**
     * Gateway must define const BLACKLISTED_COLUMNS of black listed
     * columns which should not be included in the output file.
     *
     * @return array
     */
    public function getBlackListedColumnHeadersForOutputFile()
    {
        $className = get_class($this);

        // check if constant BLACKLISTED_COLUMNS defined in subreconciliator
        $defined = defined($className . '::' . 'BLACKLISTED_COLUMNS');

        if ($defined === false)
        {
            // This function is called from 2 flows. First, while creating
            // output file. That time calling class is gateway subreconciliator.
            // Second, while tracing row. In case of combined reconciliate
            // gateways, the calling class is either payment or refund reconciliate
            // (and not the gateway subreconciliator i.e. combinedReconciliate).
            // So checking for 2nd case here.
            $parentNamespace = $this->getParentNamespace();

            // SubReconciliator class name should be something like - Reconciliator/Hitachi/CombinedReconciliate
            $className = $parentNamespace . '\\' . 'CombinedReconciliate';

            $defined = defined($className  . '::' . 'BLACKLISTED_COLUMNS');

            if ($defined === false)
            {
                $this->trace->info(TraceCode::RECON_INFO_ALERT,
                    [
                        'info_code' => InfoCode::RECON_BLACKLISTED_COLUMNS_NOT_DEFINED,
                        'gateway' => $this->gateway,
                    ]);

                return null;
            }
        }

        return constant($className . '::' . 'BLACKLISTED_COLUMNS');
    }

    protected function getParentNamespace()
    {
        // Gets the namespace from the called class, by removing the last part of the FQCN.
        return join('\\', explode('\\', get_called_class(), -1));
    }

    /**
     * Returns merged array of black listed and
     * PII columns defined for gateway
     *
     * @return array
     */
    protected function getPiiColumnHeadersForLogs()
    {
        $blacklistedColumns = $this->getBlackListedColumnHeadersForOutputFile();

        $className = get_class($this);

        // check if constant PII_COLUMNS defined in gateway subreconciliator
        $defined = defined($className . '::' . 'PII_COLUMNS');

        $piiColumns = [];

        if ($defined === true)
        {
            $piiColumns = constant($className . '::' . 'PII_COLUMNS');
        }

        return array_unique(array_merge(($blacklistedColumns ?? []), $piiColumns));
    }

    /**
     * Child gateway sub reconciliator need to override
     * this function if MIS file need to be modified.
     *
     * Currently this is being used for cardfssbob
     * @param $row
     */
    protected function modifyRowIfNeeded(&$row)
    {
        return;
    }

    /**
     * Child gateway sub reconciliator need to override
     * this function, UPI Transactions kept turning up in
     * MIS. Although they already were reconciled, their
     * being in the file, threw slack alerts.
     *
     * Currently only used in HDFC.
     * @param $row
     * @return bool
     */
    protected function skipRestOfFile($row)
    {
        return false;
    }
}
