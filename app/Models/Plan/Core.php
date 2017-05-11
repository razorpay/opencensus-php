<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Item;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->trace->info(
            TraceCode::PLAN_CREATE_REQUEST,
            $input);

        return $this->transaction([$this, 'createPlan'], $input, $merchant);
    }

    protected function createPlan(array $input, Merchant\Entity $merchant) : Entity
    {
        $this->repo->assertTransactionActive();

        $plan = (new Entity)->build($input);

        $item = (new Item\Core)->getOrCreateItemForType($input, $merchant, Item\Type::PLAN);

        $plan->merchant()->associate($merchant);

        $plan->item()->associate($item);

        $this->repo->saveOrFail($plan);

        return $plan;
    }
}
