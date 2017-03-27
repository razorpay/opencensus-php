<?php

namespace RZP\Models\Report;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param   $input
     * @return  Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::REPORT_CREATE_REQUEST,
            $input
        );

        $report = new Entity;

        $report->merchant()->associate($merchant);

        $report->build($input);

        $this->repo->saveOrFail($report);

        return $report;
    }
}
