<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input) : array
    {
        $plan = (new Core)->create($input, $this->merchant);

        return $plan->toArray();
    }
}
