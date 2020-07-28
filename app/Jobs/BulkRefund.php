<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Admin;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

class BulkRefund extends Job
{
    //
    // Make sure that this is below 900 (seconds) because
    // SQS doesn't support delay over 15 minutes.
    //
    public $delay = 50;

    protected $trace;

    protected $data;

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::BULK_REFUND_QUEUE_REQUEST,
            $this->data
        );

        try
        {
            $refundStatus = $this->runBulkRefundFlowForQueue();

            $this->trace->info(
                TraceCode::BULK_REFUND_QUEUE_SUCCESS,
                [
                    'data'          => $this->data,
                    'refund_status' => $refundStatus,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BULK_REFUND_JOB_FAILURE_EXCEPTION,
                $this->data);
        }
        finally
        {
            $this->delete();
        }
    }

    protected function runBulkRefundFlowForQueue()
    {
        $refundId = $this->data['id'];

        $verify = $this->data['verify'] ?? true;

        $retryData = [];

        $refund = $this->repoManager->refund->findOrFailPublic($refundId);

        $merchant = $refund->merchant;

        $paymentProcessor = new Payment\Processor\Processor($merchant);

        if ($verify === false)
        {
            // Will be passing this flag for skipping verify before retry
            $retryData[RefundConstants::SKIP_REFUND_VERIFY] = true;
        }

        return $paymentProcessor->processRefundRetry($refund, $retryData);
    }
}
