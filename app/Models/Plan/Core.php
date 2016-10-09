<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant)
    {
        $plan = (new Entity)->build($input);

        $plan->merchant()->associate($merchant);

        $this->repo->saveOrFail($plan);

        return $plan;
    }
}