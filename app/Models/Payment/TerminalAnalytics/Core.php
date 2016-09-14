<?php

namespace RZP\Models\Payment\TerminalAnalytics;

use RZP\Models\Base;
use RZP\Models\Payment\TerminalAnalytics;

class Core extends Base\Core
{
    public function create($input)
    {
        SD("reached here");
        $auditLog = (new TerminalAnalytics\Entity)->build($input);

        $this->repo->saveOrFail($auditLog);

        return $auditLog;
    }
}