<?php


namespace RZP\Models\Reward;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;


class Core extends Base\Core
{
    public function create($input)
    {
        $this->trace->info(TraceCode::REWARD_CREATE_REQUEST, $input);

        $reward = new Entity;

        $reward->build($input);

        if (isset($input['starts_at']) === false)
        {
            $reward->setStartsAt(Carbon::today()->getTimestamp());
        }

        $this->repo->saveOrFail($reward);

        return $reward;
    }

    public function fetchReward($merchantId)
    {
        $merchantRewards = $this->repo->merchant_reward->fetchMerchantRewardByMerchantId($merchantId);

        $response = [];

        foreach ($merchantRewards as $merchantReward)
        {
            $reward = $this->repo->reward->find($merchantReward->getRewardId());

            $response [] = array_merge($reward->toArrayPublic(), $merchantReward->toArrayPublic());
        }

        return $response;
    }
}
