<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

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
     * Queues Generate-report for merchant
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @param $entity string
     * @return void
     */
    public function generateReport(array $input, string $entity)
    {
        (new Core)->queueGenerateReport($input, $entity);
    }
}
