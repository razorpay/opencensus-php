<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function create(array $input) : array
    {
        $plan = (new Core)->create($input, $this->merchant);

        return $plan->toArrayPublic();
    }

    public function fetch(string $id): array
    {
        $plan = $this->repo->plan
                           ->findByPublicIdAndMerchant($id, $this->merchant);

        return $plan->toArrayPublic();
    }

    public function fetchMultiple(array $input): array
    {
        $plans = $this->repo->plan->fetch($input, $this->merchant->getId());

        return $plans->toArrayPublic();
    }
}
