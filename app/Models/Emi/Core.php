<?php

namespace RZP\Models\Emi;

use RZP\Exception;
use RZP\Models\Base;

class Core extends Base\Core
{
    public function addEmiPlan($input)
    {
        $emiPlan = (new Entity)->build($input);

        $emiPlan->generateId();

        $this->repo->saveOrFail($emiPlan);

        return $emiPlan;
    }
}
