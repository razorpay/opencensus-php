<?php

namespace RZP\Models\Report;

use RZP\Trace\Trace;
use RZP\Models\Base;

class Service extends Base\Service
{
    public function fetchMultiple($input)
    {
        $reports = $this->repo->report->fetch($input, $this->merchant->getId());

        return $reports->toArrayPubilc();
    }
}
