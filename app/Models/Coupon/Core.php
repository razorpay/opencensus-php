<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $coupon = (new Entity)->build($input);

        $this->repo->saveOrFail($coupon);

        return $coupon;
    }
}
