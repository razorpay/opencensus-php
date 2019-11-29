<?php

namespace RZP\Reconciliator\Base\Foundation;

use App;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity;
use RZP\Reconciliator\Core;
use RZP\Constants\Timezone;
use RZP\Reconciliator\Messenger;
use RZP\Exception\LogicException;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\RequestProcessor;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\Payment\Entity as PaymentEntity;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
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

    /**
     * For few gateways, we do not get the RZP  payment/refund ID
     * in the MIS row. We want to add extra column recon_entity_id
     * in the output file only for such gateways.
     * This variable need to be overridden and set to 'true' in
     * such gateways.
     *
     * @var bool
     */
    const SHOULD_ADD_ENTITY_ID_COLUMN = false;

    const THRESHOLD = [
        InfoCode::AMOUNT_MISMATCH   =>  10,
    ];

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
     * @var array This array will contain MIS row and
     * corresponding reconciliation status and error
     * msg in any. later an output file will be created.
     */
    protected static $reconOutputData = [];

    protected static $currentRowNumber = -1;

    public function __construct(string $gateway = null, Batch\Entity $batch = null)
    {
        parent::__construct();

        $this->gateway = $gateway;

        $this->batch = $batch;

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

        $this->setExtraDetails($fileContents[Orchestrator::EXTRA_DETAILS]);
        unset($fileContents[Orchestrator::EXTRA_DETAILS]);

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
                    $this->setSummaryCount(self::FAILURES_SUMMARY, head($row));

                    throw $ex;
                }
                finally
                {
                    $batch->incrementProcessedCount();
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

            $this->updateBatchWithSummary($batch);
        }
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
        $row[self::RECON_ERROR_MSG]         = '';
        $row[self::RZP_MERCHANT_ID]         = '';
        $row[self::PROCESSED_AT]            = $processed_at;
        $row[self::BATCH_ID]                = '';
        $row[self::ATTEMPT_NUMBER]          = '';
        $row[self::RECON_ENTITY_ID]         = '';

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

        if (empty($errorCode) === false)
        {
            static::$reconOutputData[static::$currentRowNumber][self::RECON_ERROR_MSG] = $errorCode;
        }
    }

    /**
     * sets Recon Entity ID (payment ID / Refund ID) for the
     * current row in progress
     *
     * @param string $reconEntityId
     */
    protected function setReconEntityIdInOutput(string $reconEntityId)
    {
        static::$reconOutputData[static::$currentRowNumber][self::RECON_ENTITY_ID] = $reconEntityId;
    }

    protected function setMerchantIdInOutput(string $merchantId)
    {
        static::$reconOutputData[static::$currentRowNumber][self::RZP_MERCHANT_ID] = $merchantId;
    }

    protected function setBatchIdInOutput($batchId)
    {
        static::$reconOutputData[static::$currentRowNumber][self::BATCH_ID] = $batchId;
    }

    protected function setAttemptsInOutput($attemptNumber)
    {
        static::$reconOutputData[static::$currentRowNumber][self::ATTEMPT_NUMBER] = $attemptNumber;
    }

    /**
     * For certain rows, where we are not able to successfully identify the payment
     * or refund entity to reconcile, we mark the row processing as success or failure
     * depending on the specific gateway's reconciliator.
     *
     * @param  array $row
     */
    protected function handleUnprocessedRow(array $row)
    {
        $rowStatus = ($this->failUnprocessedRow === true) ? 'Failed' : 'Success';

        $this->trace->info(TraceCode::RECON_UNPROCESSED_ROW,
            [
                'gateway' => $this->gateway,
                'status'  => $rowStatus,
                'row'     => $row,
            ]);

        //
        // Making identifier as empty string if it is null as setSummaryCount expects string identifier.
        //
        $identifier = head($row) ?? '';

        $this->setSummaryCount(self::TOTAL_SUMMARY, $identifier);

        if ($this->failUnprocessedRow === true)
        {
            $this->setSummaryCount(self::FAILURES_SUMMARY, head($row));
        }
        else
        {
            // update the recon status in the output file
            $this->setRowReconStatusAndError(InfoCode::RECON_UNPROCESSED_SUCCESS);

            $this->setSummaryCount(self::SUCCESSES_SUMMARY, head($row));
        }
    }

    protected function handleFailedValidation(string $entityId)
    {
        // Increment the failure count for the summary.
        $this->setSummaryCount(self::FAILURES_SUMMARY, $entityId);
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
                'row'               => $row,
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
            $this->messenger->raiseReconAlert(
                [
                    'info_code' => InfoCode::RECON_BLACKLISTED_COLUMNS_NOT_DEFINED,
                    'gateway'   => $this->gateway,
                ]);

            return null;
        }

        return constant($className . '::' . 'BLACKLISTED_COLUMNS');
    }
}
