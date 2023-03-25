<?php

namespace RZP\Reconciliator;

use RZP\Models\Base;
use RZP\Models\Batch;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Models\Payment\Refund;
use RZP\Reconciliator\Base\InfoCode;
use RZP\Reconciliator\Metrics\Metric;
use RZP\Reconciliator\Base\Reconciliate;
use RZP\Reconciliator\Base\Foundation\ScroogeReconciliate;

class Core extends Base\Core
{
    const MUTEX_LOCK_TIMEOUT = 900;

    /**
     * The MUTEX instance
     */
    protected $mutex;

    protected $messenger;

    public function __construct()
    {
        parent::__construct();

        $this->mutex     = $this->app['api.mutex'];
        $this->messenger = new Messenger;
    }

    public function shouldForceUpdateArnAfterScroogeRecon($input)
    {
        //
        // When we run local tests, then after post request, (bool) TRUE converts
        // to (string) "1", and false converts to string "0".
        // empty() check will return true if the field is not set or the value is
        // either false or string "0"
        //
        if (empty($input[ScroogeReconciliate::SHOULD_FORCE_UPDATE_ARN]) === false)
        {
            return true;
        }

        return false;
    }

    /** Checks if refund transaction exists.
     *  Attempt to create one, if absent and return updated refund entity.
     *
     * @param Refund\Entity $refund
     * @return Refund\Entity
     */
    public function checkAndCreateIfRefundTransactionMissing(Refund\Entity $refund): Refund\Entity
    {
        $refundTransaction = $refund->transaction;

        if ($refundTransaction === null)
        {
            $createTransactionSuccess = $this->attemptToCreateMissingRefundTransaction($refund);

            if ($createTransactionSuccess === false)
            {
                $this->messenger->raiseReconAlert(
                    [
                        'trace_code'    => TraceCode::RECON_MISMATCH,
                        'message'       => 'Refund transaction not found in DB',
                        'refund_id'     => $refund->getId(),
                        'amount'        => $refund->getAmount(),
                        'gateway'       => $refund->getGateway(),
                    ]);
            }
            else
            {
                // Refresh both refund and transaction to get latest changes.
                // Reload txn because relation are cached.
                $refund->reload()->transaction->reload();
            }
        }

        return $refund;
    }

    public function attemptToCreateMissingRefundTransaction(Refund\Entity $refund)
    {
        $paymentTransaction = $refund->payment->transaction;

        if ($paymentTransaction === null)
        {
            return false;
        }

        $this->mutex->acquireAndRelease(
            $refund->getId(),
            function () use ($refund)
            {
                try
                {
                    $txn = $this->createMissingRefundTransaction($refund);

                    if (($txn === null) and
                        (($refund->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)))
                    {
                        return false;
                    }

                    return true;
                }
                catch (\Exception $ex)
                {
                    $this->messenger->raiseReconAlert(
                        [
                            'trace_code'    => TraceCode::REFUND_TRANSACTION_CREATE_FAILED,
                            'message'       => 'Refund transaction create failed with -> ' . $ex->getMessage(),
                            'payment_id'    => $refund->payment->getId(),
                            'refund_id'     => $refund->getId(),
                            'gateway'       => $refund->getGateway(),
                        ]);

                    $this->trace->traceException($ex);

                    return false;
                }
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );
    }

    protected function createMissingRefundTransaction(Refund\Entity $refund)
    {
        $refund->reload();

        if ($refund->transaction !== null)
        {
            return $refund->transaction;
        }

        $this->trace->info(
            TraceCode::REFUND_TRANSACTION_CREATE_RECON,
            [
                'payment_id'    => $refund->payment->getId(),
                'refund_id'     => $refund->getId(),
                'gateway'       => $refund->getGateway()
            ]);

        $processor = new Payment\Processor\Processor($refund->merchant);

        $txn = $processor->createTransactionForRefund($refund, $refund->payment);

        // This is required to save the association of the transaction with the refund.
        $this->repo->saveOrFail($refund);

        return $txn;
    }

    /**
     * Persists reconciledAt from scrooge response
     * If mismatch, add a trace and overwrite the value
     *
     * @param Refund\Entity $refund
     * @param $data
     * @param $source
     */
    public function persistReconciledAtAfterScroogeRecon(Refund\Entity $refund, $data, $source)
    {
        $apiReconciledAt = $refund->transaction->getReconciledAt();

        $scroogeReconciledAt = $data[Transaction\Entity::RECONCILED_AT];

        if ((empty($apiReconciledAt) === false) and ($apiReconciledAt !== $scroogeReconciledAt))
        {
            $this->trace->info(
                TraceCode::RECON_INFO_ALERT,
                [
                    'info_code'             => InfoCode::API_SCROOGE_RECONCILED_AT_MISMATCH,
                    'refund_id'             => $refund->getId(),
                    'api_reconciled_at'     => $apiReconciledAt,
                    'scrooge_reconciled_at' => $scroogeReconciledAt,
                    'gateway'               => $refund->getGateway(),
                ]
            );
        }

        $refund->transaction->setReconciledAt($scroogeReconciledAt);

        $refund->transaction->setReconciledType(Transaction\ReconciledType::MIS);

        $this->pushSuccessRefundReconMetrics($refund, $source);
    }

    public function updateScroogeBatchSummary(string $batchId, int $totalSuccessCount, int $totalFailureCount)
    {
        $batch = $this->repo->batch->findOrFail($batchId);
        //
        // Since we might get multiple scrooge responses (due to chunks) containing
        // the same recon batch_id and here we are updating the batch success/failure
        // count and status, Need to take lock to avoid issue due to concurrent updates
        //
        $this->mutex->acquireAndRelease(
            $batchId,
            function () use ($batch, $totalSuccessCount, $totalFailureCount)
            {
                $batch->reload();

                $batch->incrementFailureCount($totalFailureCount);

                $batch->incrementSuccessCount($totalSuccessCount);

                $this->setStatusAndProcessingFlagIfApplicable($batch);

                $this->repo->batch->saveOrFail($batch);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS,
            10,
            300,
            900);
    }

    /**
     * Checks if the success and failure count sum is now equal to total
     * processed_count. This indicates that no chunk (scrooge response)
     * is pending to be processed now, and we can update the batch status
     * to 'processed/partially_processed' and `processing` flag to false now.
     *
     * @param $batch
     */
    protected function setStatusAndProcessingFlagIfApplicable($batch)
    {
        if (($batch->getSuccessCount() + $batch->getFailureCount()) === $batch->getProcessedCount())
        {
            $reconBatchProcessor = new Batch\Processor\Reconciliation($batch);

            // update 'processing' flag
            $reconBatchProcessor->updateStatusPostProcess();

            // update status
            $reconBatchProcessor->setStatusAfterSuccessfulProcessing();

            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'             => InfoCode::RECON_BATCH_MARK_PROCESSED,
                    'success_count'         => $batch->getSuccessCount(),
                    'failure_count'         => $batch->getFailureCount(),
                    'total_processed_count' => $batch->getProcessedCount(),
                    'batch_id'              => $batch->getId(),
                ]
            );

            (new Reconciliate)->traceBatchProcessingSummary($batch);

            $this->trace->info(TraceCode::BATCH_FILE_PROCESSED, $batch->toArrayTraceAll());
        }
        else
        {
            $this->trace->info(
                TraceCode::RECON_INFO,
                [
                    'info_code'             => InfoCode::RECON_BATCH_NOT_LAST_CHUNK,
                    'success_count'         => $batch->getSuccessCount(),
                    'failure_count'         => $batch->getFailureCount(),
                    'total_processed_count' => $batch->getProcessedCount(),
                    'batch_id'              => $batch->getId(),
                ]
            );
        }
    }

    public function pushSuccessPaymentReconMetrics(Payment\Entity $payment, $source)
    {
        $this->trace->histogram(
            Metric::RECON_PAYMENT_CREATE_TO_RECONCILED_TIME_MINUTES,
            $payment->transaction->getReconTimeFromTransactionCreationInMinutes(),
            Metric::getPaymentMetricDimensions($payment, $source)
        );
    }

    public function pushRefundProcessedMetric(Refund\Entity $refund, $source)
    {
        $this->trace->histogram(
            Metric::RECON_REFUND_CREATED_TO_PROCESSED_TIME_MINUTES,
            $refund->getTimeFromCreatedInMinutes(),
            Metric::getRefundMetricDimensions($refund, $source));
    }

    public function pushSuccessRefundReconMetrics(Refund\Entity $refund, $source)
    {
        $this->trace->histogram(
            Metric::RECON_REFUND_CREATE_TO_RECONCILED_TIME_MINUTES,
            $refund->transaction->getReconTimeFromTransactionCreationInMinutes(),
            Metric::getRefundMetricDimensions($refund, $source)
        );
    }
}
