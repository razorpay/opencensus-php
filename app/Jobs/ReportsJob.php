<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Report\Types\BasicEntityReport;

class ReportsJob extends Job
{
    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 300;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    protected $input;

    protected $entity;

    protected $merchantId;

    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        array $input,
        string $entity,
        string $merchantId,
        string $mode)
    {
        parent::__construct($mode);

        $this->input      = $input;
        $this->entity     = $entity;
        $this->merchantId = $merchantId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $reportType = new BasicEntityReport($this->entity);

            $reportType->setMerchant($this->merchantId);

            $reportType->generateReport($this->input);

            $this->delete();
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * When an exception occurs, the job gets deleted if it has
     * exceeded the maximum attempts. Otherwise it is released back
     * into the queue after the set release wait time
     *
     * @param Exception $e
     */
    protected function handleException(\Exception $e)
    {
        $jobAction = self::JOB_DELETED;

        if ($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::REPORT_REQUEST_FAILED,
                        [
                            'job_action' => $jobAction,
                        ]);
    }
}
