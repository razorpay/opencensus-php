<?php


namespace RZP\Models\Reward;

use RZP\Models\Base;
use Carbon\Carbon;


class Repository extends Base\Repository
{
    protected $entity = 'reward';

    public function update(string $id, array $params)
    {
        $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->update($params);
    }

    public function fetchUpdatedRewardColumnsById(string $rewardId, array $params)
    {
        $query = $this->newQuery()
            ->select($params)
            ->where(Entity::ID, '=', $rewardId);
        return $query->first();
    }

    public function setUniqueCouponsExhaustedForReward($rewardId)
    {
        $this->newQuery()
            ->where(Entity::ID, '=', $rewardId)
            ->update([
                Entity::UNIQUE_COUPONS_EXHAUSTED => true,
                Entity::UPDATED_AT               => Carbon::now()->getTimestamp()
            ]);
    }
}
