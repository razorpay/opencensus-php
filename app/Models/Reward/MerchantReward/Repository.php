<?php


namespace RZP\Models\Reward\MerchantReward;

use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Reward\Entity as RewardEntity;


class Repository extends Base\Repository
{
    protected $entity = 'merchant_reward';

    //
    // Default order defined in RepositoryFetch is created_at, id
    // Overriding here because pivot table does not have an id col.
    //
    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc');
    }

    public function fetchAvailableMerchantRewardByMerchantIdAnRewardId($merchantId, $rewardId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->where(Entity::STATUS, '=', Entity::AVAILABLE);

        return $query->first();
    }


    public function fetchLiveMerchantRewardByMerchantIdAnRewardId($merchantId, $rewardId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->whereIn(Entity::STATUS, [Entity::LIVE, Entity::QUEUE]);

        return $query->first();
    }

    public function fetchExpiredMerchantReward()
    {
        $now = $now = Carbon::now()->getTimestamp();

        $merchantRewardId = $this->repo->merchant_reward->dbColumn(Entity::REWARD_ID);

        $rewardId = $this->repo->reward->dbColumn(RewardEntity::ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Table::MERCHANT_REWARD.'.*')
            ->join(Table::REWARD, $merchantRewardId, '=', $rewardId)
            ->where(RewardEntity::ENDS_AT, '<=', $now)
            ->whereNotIn(Entity::STATUS, [Entity::EXPIRED, Entity::DELETED]);

        return $query->get();
    }

    public function fetchMerchantRewardByRewardId($rewardId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::REWARD_ID, '=', $rewardId);

        return $query->get();
    }

    public function fetchMerchantRewardByMerchantId($merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->whereIn(Entity::STATUS, [Entity::LIVE, Entity::QUEUE, Entity::AVAILABLE]);

        return $query->get();
    }

    public function fetchCountOfLiveRewardByMerchantId($merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::LIVE);

        return $query->count();
    }

    public function fetchNextRewardIdInQueue($merchantId)
    {
        $now = $now = Carbon::now()->getTimestamp();

        $merchantRewardId = $this->repo->merchant_reward->dbColumn(Entity::REWARD_ID);

        $rewardId = $this->repo->reward->dbColumn(RewardEntity::ID);

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Table::MERCHANT_REWARD.'.*')
            ->join(Table::REWARD, $merchantRewardId, '=', $rewardId)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::QUEUE)
            ->where(RewardEntity::STARTS_AT, '<=', $now)
            ->where(RewardEntity::ENDS_AT, '>=', $now)
            ->orderBy(RewardEntity::STARTS_AT)
            ->orderBy(Entity::ACCEPTED_AT);

        return $query->first();
    }

    public function update(Entity $merchantReward, array $params)
    {
        $this->newQuery()
            ->where(Entity::REWARD_ID, '=', $merchantReward->getRewardId())
            ->where(Entity::MERCHANT_ID, '=', $merchantReward->getMerchantId())
            ->update($params);
    }

    public function fetchLiveRewardByMerchantId($merchantId)
    {

        $now = Carbon::now()->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Table::REWARD.'.*')
            ->from(TABLE::REWARD)
            ->where(RewardEntity::ENDS_AT, '>', $now)
            ->where(function($query)
            {
                $query->whereNotNull(RewardEntity::COUPON_CODE)
                      ->orWhere(function($query)
                      {
                          $query->where(RewardEntity::UNIQUE_COUPONS_EXIST, '=', 1)
                                ->where(RewardEntity::UNIQUE_COUPONS_EXHAUSTED, '=', 0);
                      });
            })
            ->whereIn(RewardEntity::ID, function($query) use($now, $merchantId) {
                $query->select(Table::MERCHANT_REWARD.'.'.(Entity::REWARD_ID))
                    ->from(TABLE::MERCHANT_REWARD)
                    ->where(Entity::MERCHANT_ID,'=',$merchantId)
                    ->where(Entity::STATUS, '=', 'live') ;
            });

        return $query->get()->toArray();
    }

    public function fetchQueueMerchantRewards()
    {
        $now = Carbon::now()->getTimestamp();

        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->select(Table::MERCHANT_REWARD.'.*')
            ->where(Entity::STATUS, '=', Entity::QUEUE)
            ->whereIn(Entity::REWARD_ID, function($query) use($now) {
                $query->select('rewards.id')
                      ->from(TABLE::REWARD)
                      ->where(RewardEntity::STARTS_AT, '<=', $now) ;
            });

        return $query->get();
    }

    public function fetchLiveMerchantRewardByRewardIdAndMerchantId($rewardId, $merchantId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::LIVE);

        return $query->first();
    }

    public function fetchMerchantIdsByRewardId(string $rewardId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::REWARD_ID, '=', $rewardId);
        return $query->get();
    }

    public function updateMerchant($merchantId,  $rewardId, array $params)
    {
        $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->update($params);
    }

    public function fetchMerchantRewardByMerchantIdAndRewardId($merchantId, $rewardId)
    {
        $query = $this->newQueryWithConnection($this->getSlaveConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId);
            return $query->first();
    }

}
