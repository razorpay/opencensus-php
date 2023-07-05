<?php

namespace RZP\Models\Transfer;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class PaymentTransfer extends  AbstractTransfer
{
    /**
     * OrderTransfer constructor.
     */

    public function __construct($payment)
    {
        parent::__construct($payment);

    }

    public function process()
    {
        $mutexConfig = $this->fetchTransferProcessMutexConfig();

        $mutexNumRetries = (int) ($mutexConfig['num_retries'] ?? self::MUTEX_NUM_RETRIES);

        $mutexMinDelayMs = (int) ($mutexConfig['min_delay_ms'] ?? self::MUTEX_MIN_RETRY_DELAY_MS);

        $mutexMaxDelayMs = (int) ($mutexConfig['max_delay_ms'] ?? self::MUTEX_MAX_RETRY_DELAY_MS);

        $mutexLockTimeoutSec = (int) ($mutexConfig['lock_timeout_sec'] ?? self::MUTEX_LOCK_TIMEOUT);

        try
        {
            [$transfersProcessed, $failedTransfersToRetry] = $this->mutex->acquireAndRelease(
                'payment_transfer_process_' . $this->payment->getPublicId(),
                function () {

                    $payment =  $this->payment;

                    $this->sourceId = $payment->getId();

                    $this->tracecode = TraceCode::PAYMENT_TRANSFER_PROCESSING;

                    $this->invalidCode = TraceCode::PAYMENT_TRANSFER_PROCESS_INVALID_REQUEST;

                    $this->failurecode = TraceCode::PAYMENT_TRANSFER_PROCESS_FAILURE;

                    $this->transfermode = Constant::PAYMENT;

                    $this->status = [Status::PENDING];

                    return $this->processOrderTransfers($this->payment);
                },
                $mutexLockTimeoutSec, ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_PROCESS_IN_PROGRESS, $mutexNumRetries,
                $mutexMinDelayMs, $mutexMaxDelayMs, true);

            $this->trace->info(
                TraceCode::PAYMENT_TRANSFER_PROCESS_SUCCESS,
                [
                    'payment_id' => $this->payment->getPublicId(),
                    'count'      => count($transfersProcessed->getIds())
                ]
            );

            return [$transfersProcessed, $failedTransfersToRetry];
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::PAYMENT_TRANSFER_PROCESS_FAILURE,
                [
                    'payment_id' => $this->payment->getPublicId()
                ]
            );

            (new Metric())->pushTransferProcessFailedMetrics($e);
        }
    }
}
