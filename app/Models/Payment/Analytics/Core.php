<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Models\Base;
use RZP\Models\Payment\Analytics;

class Core extends Base\Core
{
    public function create($input)
    {
        $auditLog = (new Analytics\Entity)->build($input);

        $this->repo->saveOrFail($auditLog);

        return $auditLog;
    }

}
