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

    public function updateCredits(Entity $merchantPromotion)
    {
        //TODO fill me
    }

    public function applyCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign' => $promotion->getName(),
            'value'    => $promotion->getAmount(),
            'type'     => $promotion->getType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    public function expireCredits($merchant, $promotion)
    {
        $creditInput = [
            'campaign' => $promotion->getName(),
            'value'    => $this->calculateCreditToExpire() * -1,
            'type'     => $promotion->getType(),
        ];

        (new Credits\Core)->create($merchant, $creditInput);
    }

    protected function calculateCreditToExpire($merchant, $promotion)
    {
        //TODO implement this
        return 0;
    }
}
