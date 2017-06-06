<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Core extends Base\Core
{
    public function create($merchant, $promotion)
    {
        $input = [
            Entity::REMAINING_RUNS => $promotion->getIterations(),
            Entity::START_TIME     => time(),
        ];

        $merchantPromotion = (new Entity)->build($input);

        $merchantPromotion->merchant()->associate($merchant);

        $merchantPromotion->promotion()->associate($promotion);

        $this->repo->saveOrFail($merchantPromotion);

        return $merchantPromotion;
    }
}
