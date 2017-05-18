<?php

namespace RZP\Jobs;

use App;

use RZP\Jobs\Job;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\Report\Types\BasicEntityReport;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReportsJob extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    const MAX_ALLOWED_ATTEMPTS = 5;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    protected $input;

    protected $entity;

    protected $merchantId;

    protected $mode;

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
        $this->input = $input;

        $this->entity = $entity;

        $this->merchantId = $merchantId;

        $this->mode = $mode;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try
        {
            $this->init();

            $reportType = new BasicEntityReport($this->entity);

            $reportType->setMerchant($this->merchantId);

            $reportType->generateReport($this->input);
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }
    }

    /**
     *  Initializes instance variables: core, trace etc.
     *
     * @return null
     */
    protected function init()
    {
        $app = App::getFacadeRoot();

        $this->trace = $app['trace'];

        //
        // Set application mode as well as database connection with given mode.
        //
        $app['rzp.mode'] = $this->mode;

        \Database\DefaultConnection::set($this->mode);
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

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
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
            ['job_action' => $jobAction]);
    }
}
