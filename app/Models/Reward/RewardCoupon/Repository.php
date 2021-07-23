<?php


namespace RZP\Models\Reward\RewardCoupon;

use Carbon\Carbon;
use RZP\Models\Base;


class Repository extends Base\Repository
{
    protected $entity = 'reward_coupon';

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function fetchCouponByRewardIdWithLock($rewardId)
    {
        assertTrue ($this->isTransactionActive());

        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::REWARD_ID, '=', $rewardId)
                    ->where(Entity::STATUS, '=', Entity::AVAILABLE)
                    ->first();
    }

    public function updateCouponStatus(Entity $rewardCoupon)
    {
        $this->newQuery()
            ->where(Entity::REWARD_ID, '=', $rewardCoupon->getRewardId())
            ->where(Entity::COUPON_CODE, '=', $rewardCoupon->getCouponCode())
            ->update([
                Entity::STATUS     => Entity::USED,
                Entity::UPDATED_AT => Carbon::now()->getTimestamp()
            ]);
    }

    public function getAvailableCouponCount($rewardId)
    {
        return $this->newQuery()
                    ->where(Entity::REWARD_ID, '=', $rewardId)
                    ->where(Entity::STATUS, '=', Entity::AVAILABLE)
                    ->count();
    }
}
