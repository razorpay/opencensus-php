<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Core extends Base\Core
{
    public function __construct()
    {
        $this->repo = new Repository;
    }

    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }
}
