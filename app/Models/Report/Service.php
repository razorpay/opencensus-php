<?php

namespace RZP\Models\Report;

use RZP\Models\Base;

class Service extends Base\Service
{
    /**
     * Fetches reports for merchant
     *
     * @param $input array
     *        expected : null, 'type'
     * @return array
     */
    public function fetchMultiple(array $input)
    {
        $reports = $this->repo->report->fetch($input, $this->merchant->getId());

        return $reports->toArrayPublic();
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
