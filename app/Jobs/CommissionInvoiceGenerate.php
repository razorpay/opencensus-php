<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Metric;
use RZP\Models\Partner\Commission\Invoice;

class CommissionInvoiceGenerate extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    public $timeout = 1000;

    /**
     * @var array
     */
    protected $data;

    public function __construct(string $mode, array $data)
    {
        parent::__construct($mode);

        $this->data = $data;
    }

    public function handle()
    {
        $startTime = millitime();

        parent::handle();

        try
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_GENERATE_JOB_REQUEST,
                [
                    'mode'   => $this->mode,
                    'data'   => $this->data,
                ]);

            $summary = (new Invoice\Core)->bulkGenerateCommissionInvoice($this->data);

            $this->trace->info(TraceCode::COMMISSION_INVOICE_GENERATE_SUMMARY, $summary);

            // if there are any failed ids, we will retry
            if ($summary['failed_count'] > 0)
            {
                $this->checkRetry();
            }
            else
            {
                $this->delete();
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_GENERATE_JOB_ERROR,
                [
                    'mode'   => $this->mode,
                    'data'   => $this->data,
                ]
            );
            $this->trace->count(Metric::COMMISSION_INVOICE_GENERATE_RETRY_EXHAUSTED_TOTAL);
            $this->checkRetry($e);
        }

        $timeTaken = millitime() - $startTime;

        $this->trace->histogram(Metric::COMMISSION_INVOICE_GENERATION_JOB_PROCESSING_IN_MS, $timeTaken);
    }

    protected function checkRetry(\Throwable $e = null)
    {
        if ($e !== null)
        {
            $this->countJobException($e);
        }

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::COMMISSION_INVOICE_GENERATE_QUEUE_DELETE,
                [
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );
            $this->trace->count(Metric::COMMISSION_INVOICE_GENERATE_JOB_FAILED_TOTAL);
            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }

}
