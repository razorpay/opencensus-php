<?php

namespace RZP\Jobs;

use App;

use RZP\Jobs\BaseJob;
use RZP\Trace\TraceCode;
use RZP\Models\Report\Types\EntityReport;

class ReportsJob extends BaseJob
{
    protected $input;

    protected $entity;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $input, string $entity)
    {
        $this->input = $input;

        $this->entity = $entity;
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

            $reportType->generateReport($this->input);
        }
        catch (\Exception $e)
        {
            $this->handleException($e, TraceCode::REPORT_REQUEST_FAILED);
        }
    }
}
