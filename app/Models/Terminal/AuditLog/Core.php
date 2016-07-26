<?php

namespace RZP\Models\Terminal\AuditLog;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Terminal\AuditLog;

class Core extends Base\Core
{
    public function create($input)
    {
        $auditLog = (new AuditLog\Entity)->build($input);

        //$this->validateExistingAction($action);

        $this->repo->terminal_auditlog->saveOrFail($auditLog);

        return $auditLog;
    }

}
