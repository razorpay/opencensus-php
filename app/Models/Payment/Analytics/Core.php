<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Payment\Analytics;

class Core extends Base\Core
{
    public function create($input)
    {
        $auditLog = (new Analytics\Entity)->build($input);

        //$this->validateExistingAction($action);

        $this->repo->payment_analytics->saveOrFail($auditLog);

        return $auditLog;
    }
}