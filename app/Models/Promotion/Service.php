<?php

namespace RZP\Models\Promotion;

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
        $this->trace->info(TraceCode::PROMOTION_CREATE_REQUEST, $input);

        $promotion = $this->core->create($input);

        return $promotion->toArrayPublic();
    }

    public function update(string $id, array $input)
    {
        $this->trace->info(TraceCode::PROMOTION_UPDATE_REQUEST, $input);

        $promotion = $this->repo->promotion->findByPublicId($id);

        $promotion = $this->core->update($offer, $input);

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
