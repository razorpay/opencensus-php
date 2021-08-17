<?php

namespace RZP\Models\Payout\Batch;

use RZP\Models\Base\Service as BaseService;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->entityRepo = $this->repo->payouts_batch;

        $this->core = (new Core());
    }

    use ServiceHasCrudMethods;
}
