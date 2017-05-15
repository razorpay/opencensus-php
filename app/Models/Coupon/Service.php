<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;
    }

    public function create(array $input)
    {
        $this->trace->info(TraceCode::COUPON_CREATE_REQUEST, $input);

        $coupon = $this->core->create($input);

        return $promotion->toArrayPublic();
    }


    public function fetch(string $id)
    {
        $promotion = $this->repo->merchant->findByPublicId($id);

        return $promotion->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $promotions = $this->repo->promotion->fetch($input);

        return $promotions->toArrayPublic();
    }
}
