<?php

namespace RZP\Models\BulkWorkflowAction;

use Request;
use RZP\Models\Base;
use RZP\Models\Workflow\Action;

class Service extends Base\Service
{
    public function executeBulkAction(array $input)
    {
        return $this->core()->executeBulkAction($input);
    }
}
