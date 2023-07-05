<?php

namespace RZP\Models\Transfer;

use RZP\Models\Admin;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class OrderTransfer extends  AbstractTransfer
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

        $mutexNumRetries = (int) ($mutexConfig['num_retries'] ?? 0);

        $mutexMinDelayMs = (int) ($mutexConfig['min_delay_ms'] ?? 100);

        $mutexMaxDelayMs = (int) ($mutexConfig['max_delay_ms'] ?? 200);

        $mutexLockTimeoutSec = (int) ($mutexConfig['lock_timeout_sec'] ?? self::MUTEX_LOCK_TIMEOUT);

        try
        {
            [$transfersProcessed, $failedTransferToRetry] = $this->mutex->acquireAndRelease(
                'order_transfer_process_' . $this->payment->getPublicId(),
                function ()
                {
                    $payment = $this->payment;

                    $this->sourceId = $payment->getApiOrderId();

                    $this->tracecode = TraceCode::ORDER_TRANSFER_PROCESSING;

                    $this->invalidCode = TraceCode::ORDER_TRANSFER_PROCESS_INVALID_REQUEST;

                    $this->failurecode = TraceCode::ORDER_TRANSFER_PROCESS_FAILURE;

                    $this->transfermode = Constant::ORDER;

                    $this->status = [Status::PENDING,Status::FAILED];

                    return $this->processOrderTransfers($this->payment);
                },
                $mutexLockTimeoutSec, ErrorCode::BAD_REQUEST_ORDER_TRANSFER_PROCESS_IN_PROGRESS, $mutexNumRetries,
                $mutexMinDelayMs, $mutexMaxDelayMs, true);

            $this->trace->info(
                TraceCode::ORDER_TRANSFER_PROCESS_SUCCESS,
                [
                    'payment_id' => $this->payment->getPublicId(),
                    'count'      => count($transfersProcessed->getIds())
                ]
            );

            return $failedTransferToRetry;
        }
        catch (\Exception $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::ORDER_TRANSFER_PROCESS_FAILURE,
                [
                    'payment_id' => $this->payment->getPublicId()
                ]
            );

            (new Metric())->pushTransferProcessFailedMetrics($e);
        }
    }
}
