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

        $mutexNumRetries = $mutexConfig[Constant::TRANSFER_PROCESS_MUTEX_NUM_RETRIES_KEY];

        $mutexMinDelayMs = $mutexConfig[Constant::TRANSFER_PROCESS_MUTEX_MIN_RETRY_DELAY_MS_KEY];

        $mutexMaxDelayMs = $mutexConfig[Constant::TRANSFER_PROCESS_MUTEX_MAX_RETRY_DELAY_MS_KEY];

        $mutexLockTimeoutSec = $mutexConfig[Constant::TRANSFER_PROCESS_MUTEX_LOCK_TIMEOUT_SEC_KEY];

        $mutexResource = Core::getTransferProcessingMutexResource(Constant::ORDER, $this->payment);

        try
        {
            [$transfersProcessed, $failedTransfersToRetry] = $this->mutex->acquireAndRelease(
                $mutexResource . $this->payment->getPublicId(),
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

            return [$transfersProcessed, $failedTransfersToRetry];
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

            throw $e;
        }
    }
}
