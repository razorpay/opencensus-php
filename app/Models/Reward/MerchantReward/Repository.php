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
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->where(Entity::STATUS, '=', Entity::AVAILABLE);

        return $query->first();
    }

    public function fetchLiveMerchantRewardByMerchantIdAnRewardId($merchantId, $rewardId)
    {
        $query = $this->newQuery()
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

        $query = $this->newQuery()
            ->select(Table::MERCHANT_REWARD.'.*')
            ->join(Table::REWARD, $merchantRewardId, '=', $rewardId)
            ->where(RewardEntity::ENDS_AT, '<=', $now)
            ->whereNotIn(Entity::STATUS, [Entity::EXPIRED, Entity::DELETED]);

        return $query->get();
    }

    public function fetchMerchantRewardByRewardId($rewardId)
    {
        $query = $this->newQuery()
            ->where(Entity::REWARD_ID, '=', $rewardId);

        return $query->get();
    }

    public function fetchMerchantRewardByMerchantId($merchantId)
    {
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId);

        return $query->get();
    }

    public function fetchCountOfLiveRewardByMerchantId($merchantId)
    {
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::LIVE);

        return $query->count();
    }

    public function fetchNextRewardIdInQueue($merchantId)
    {
        $now = $now = Carbon::now()->getTimestamp();

        $merchantRewardId = $this->repo->merchant_reward->dbColumn(Entity::REWARD_ID);

        $rewardId = $this->repo->reward->dbColumn(RewardEntity::ID);

        $query = $this->newQuery()
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
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::LIVE)
            ->limit(Entity::MAX_LIVE_REWARD_ALLOWED);

        return $query->get();
    }

    public function fetchQueueMerchantRewards()
    {
        $query = $this->newQuery()
                      ->where(Entity::STATUS, '=', Entity::QUEUE);

        return $query->get();
    }

    public function fetchLiveMerchantRewardByRewardIdAndMerchantId($rewardId, $merchantId)
    {
        $query = $this->newQuery()
            ->where(Entity::REWARD_ID, '=', $rewardId)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::STATUS, '=', Entity::LIVE);

        return $query->first();
    }

    public function fetchMerchantIdsByRewardId(string $rewardId)
    {
        $query = $this->newQuery()
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
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::REWARD_ID, '=', $rewardId);
            return $query->first();
    }

}
