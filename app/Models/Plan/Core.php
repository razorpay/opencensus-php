<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Trace\TraceCode;
use RZP\Models\Item;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(
            TraceCode::PLAN_CREATE_REQUEST,
            $input);

        $plan = (new Entity)->build($input);

        $this->repo->transaction(
            function() use ($plan, $input, $merchant)
            {
                $item = (new Item\Core)->createItemForType($input, $merchant, Item\Type::PLAN);

                $plan->merchant()->associate($merchant);

                $plan->item()->associate($item);

                $this->repo->saveOrFail($plan);
            });

        return $plan;
    }
}
