<?php

namespace RZP\Jobs;

use App;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

/**
 * Calls verify on given refund data
 *
 * Class BulkScroogeVerifyRefund
 * @package RZP\Jobs
 */
class BulkScroogeVerifyRefund extends Job
{
    //
    // Make sure that this is below 900 (seconds) because
    // SQS doesn't support delay over 15 minutes.
    //
    public $delay = 5;

    protected $trace;

    protected $data;

    /**
     * @var string
     */
    protected $queueConfigKey = 'scrooge_refund_verify';

    public function __construct(array $data)
    {
        parent::__construct($data['mode']);

        $this->data = $data;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::BULK_SCROOGE_REFUND_VERIFY_QUEUE_REQUEST,
            $this->data
        );

        try
        {
            $result = $this->runBulkScroogeVerifyRefundFlowForQueue();

            $this->trace->info(
                TraceCode::BULK_SCROOGE_REFUND_VERIFY_QUEUE_SUCCESS,
                [
                    'data'          => $this->data,
                    'result'        => $result,
                ]);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::BULK_SCROOGE_REFUND_VERIFY_JOB_FAILED,
                $this->data);
        }
        finally
        {
            $this->delete();
        }
    }

    protected function runBulkScroogeVerifyRefundFlowForQueue()
    {
        $refund = $this->repoManager->refund->find($this->data[Payment\Refund\Entity::ID]);

        $merchant = $refund->merchant;

        $processor = new Payment\Processor\Processor($merchant);

        $result = $processor->verifyScroogeRefundWithAttempts($refund,
                                                              $this->data[Payment\Refund\Entity::ATTEMPTS]);

        return $result;
    }
}
