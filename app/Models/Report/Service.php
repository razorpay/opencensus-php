<?php

namespace RZP\Models\Report;

use RZP\Trace\Trace;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Jobs\ReportsJob;

use Illuminate\Foundation\Bus\DispatchesJobs;

class Service extends Base\Service
{
    use DispatchesJobs;

    /**
     * Fetches reports for merchant
     *
     * @param $input array
     *        expected : null, 'type'
     * @return array
     */
    public function fetchMultiple(array $input)
    {
        $reports = $this->repo->report->fetch($input, $this->merchant->getId())->toArrayPublic();

        return $reports;
    }

    /**
     * Generates report for merchant
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @param $entity string
     * @return void
     */
    public function generateReport(array $input, string $entity)
    {
        try
        {
            $this->dispatch(new ReportsJob($input, $entity));
        }
        catch (Exception $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::REPORT_REQUEST_FAILED);
        }
    }
}
