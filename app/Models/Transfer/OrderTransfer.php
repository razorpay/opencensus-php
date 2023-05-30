<?php

namespace RZP\Models\Transfer;

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
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ORDER_TRANSFER_PROCESS_IN_PROGRESS, 0,100,200 ,true);

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
        }
    }
}
