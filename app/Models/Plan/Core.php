<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(
            TraceCode::PLAN_CREATE_REQUEST,
            $input);

        $plan = (new Entity)->build($input);

        $plan->merchant()->associate($merchant);

        $this->repo->saveOrFail($plan);

        return $plan;
    }
}
