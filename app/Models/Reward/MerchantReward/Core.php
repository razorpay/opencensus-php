<?php


namespace RZP\Models\Reward\MerchantReward;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Reward\Entity as RewardEntity;

class Core extends Base\Core
{
    /**
     * @param $merchantId
     * @param $rewardId
     */
    public function create($merchantId, $rewardId)
    {
        $merchantReward = new Entity;

        $merchantReward->setMerchantId($merchantId);

        $merchantReward->setRewardId($rewardId);

        $merchantReward->setStatus(Entity::AVAILABLE);

        $this->repo->saveOrFail($merchantReward);
    }

    public function update($reward)
    {
        $failed_merchants_id = [];

        $now = Carbon::now()->getTimestamp();

        $merchants = $this->repo->merchant_reward->fetchMerchantIdsByRewardId($reward['id']);

        foreach($merchants as $merchant)
        {
            try
            {
                $columnsToUpdate = [];

                if($merchant[ENTITY::STATUS] == ENTITY::EXPIRED)
                {
                    $columnsToUpdate[ENTITY::UPDATED_AT] = $now;

                    if($merchant[Entity::ACCEPTED_AT] != null)
                    {
                        $columnsToUpdate[ENTITY::STATUS] = ENTITY::LIVE;
                    }
                    else
                    {
                        $columnsToUpdate[ENTITY::STATUS] = ENTITY::AVAILABLE;
                    }
                }
                $this->repo->merchant_reward->updateMerchant($merchant['merchant_id'] , $reward['id'], $columnsToUpdate);
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $failed_merchants_id[] = $merchant->getMerchantId();
            }
        }
        return $failed_merchants_id;
    }
    /**
     * @param $merchantId
     * @param $rewardId
     * @param $activate
     * @return array
     * @throws \Exception
     */
    public function activateDeactivateRewardByMerchantIdAnRewardId($merchantId, $rewardId, $activate)
    {
        $summary = null;

        $this->trace->info(TraceCode::REWARD_ACTIVATE_DEACTIVATE_REQUEST,
                          ['activate' => $activate, 'reward_id' => $rewardId, 'merchant_id' => $merchantId]);

        if (($activate === true) or
            ($activate === '1'))
        {
            $merchantReward = $this->repo->merchant_reward->fetchAvailableMerchantRewardByMerchantIdAnRewardId($merchantId,
                RewardEntity::verifyIdAndStripSign($rewardId));

            if (isset($merchantReward) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_REWARD_ACTIVATE, null, null, 'Merchant Reward not found'
                );
            }

            $reward = $this->repo->reward->findOrFail($rewardId);

            $summary = $this->activateReward($merchantId, $merchantReward, $reward);
        }
        else
        {
            $merchantReward = $this->repo->merchant_reward->fetchLiveMerchantRewardByMerchantIdAnRewardId($merchantId,
                RewardEntity::verifyIdAndStripSign($rewardId));

            if (isset($merchantReward) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_REWARD_DEACTIVATE, null, null, 'Merchant Reward not found'
                );
            }

            $summary = $this->deactivateReward($merchantId, $merchantReward);
        }

        return $summary;
    }

    /**
     * @param $rewardId
     * @return array
     */
    public function deleteRewardByRewardId($rewardId)
    {
        $merchantRewards = $this->repo->merchant_reward->fetchMerchantRewardByRewardId(
            RewardEntity::verifyIdAndStripSign($rewardId));

        $success = true;

        $this->trace->info(TraceCode::REWARD_DELETE_REQUEST, ['reward_id' => $rewardId]);

        try
        {
            $this->repo->merchant_reward->transaction(function () use($merchantRewards, $rewardId)
            {
                $columnsToUpdate = [
                    'status' => Entity::DELETED
                ];

                foreach ($merchantRewards as $merchantReward)
                {
                    $now = Carbon::now()->getTimestamp();

                    $columnsToUpdate[Entity::UPDATED_AT] = $now;

                    $this->repo->merchant_reward->update($merchantReward, $columnsToUpdate);

                    $this->moveQueueRewardToLive($merchantReward->getMerchantId());

                }

                $reward = $this->repo->reward->find($rewardId);

                $reward->setIsDeleted(true);

                $this->repo->reward->saveOrFail($reward);
            });
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            $success = false;
        }


        return $success;
    }

    /**
     * @param $merchantId
     * @param $merchantReward
     * @param $reward
     * @return array
     */
    public function activateReward($merchantId, Entity $merchantReward, RewardEntity $reward): array
    {
        $summary = [];

        $now = Carbon::now()->getTimestamp();

        $merchantReward->setAcceptedAt($now);

        $columnsToUpdate = [];


        if ($reward->getStartsAt() > $now)
        {
            $columnsToUpdate[Entity::STATUS] = Entity::QUEUE;

            $columnsToUpdate[Entity::ACCEPTED_AT] = $now;

            $summary['status'] = Entity::QUEUE;
        }
        else
        {
            $columnsToUpdate[Entity::STATUS] = Entity::LIVE;

            $columnsToUpdate[Entity::ACTIVATED_AT] = $now;

            $summary['status'] = Entity::LIVE;
        }

        $columnsToUpdate[Entity::ACCEPTED_AT] = $now;

        $columnsToUpdate[Entity::UPDATED_AT] = $now;

        $this->repo->merchant_reward->update($merchantReward, $columnsToUpdate);

        $summary['live_reward_id'] = $merchantReward->getRewardId();

        return $summary;
    }

    /**
     * @param $merchantId
     * @param $merchantReward
     * @return mixed
     * @throws \Exception
     */
    public function deactivateReward($merchantId, Entity $merchantReward)
    {
        $summary = $this->repo->merchant_reward->transaction(function () use($merchantReward, $merchantId)
        {
            $columnsToUpdate = [];

            $summary = [];

            $now = $now = Carbon::now()->getTimestamp();

            $status = $merchantReward->getStatus();

            $columnsToUpdate[Entity::STATUS] = Entity::AVAILABLE;

            $columnsToUpdate[Entity::ACCEPTED_AT] = null;

            $columnsToUpdate[Entity::ACTIVATED_AT] = null;

            $columnsToUpdate[Entity::UPDATED_AT] = $now;

            $columnsToUpdate[Entity::DEACTIVATED_AT] = $now;

            $this->repo->merchant_reward->update($merchantReward, $columnsToUpdate);

            if ($status === Entity::LIVE)
            {
                $this->moveQueueRewardToLive($merchantId);
            }

            $summary['deactivated_reward_id'] = $merchantReward->getRewardId();

            $summary[Entity::STATUS] = Entity::AVAILABLE;

            return $summary;
        });

        return $summary;
    }

    /**
     * @param $merchantId
     * @param $rewardId
     */
    public function moveQueueRewardToLive($merchantId): void
    {
        $now = Carbon::now()->getTimestamp();

        $queueMerchantReward = $this->repo->merchant_reward->fetchNextRewardIdInQueue($merchantId);

        $columnsToUpdate = [];

        if ($queueMerchantReward !== null)
        {
            $this->trace->info(TraceCode::REWARD_QUEUE_TO_LIVE, ['reward_id' => $queueMerchantReward->getRewardId()]);

            $columnsToUpdate[Entity::STATUS] = Entity::LIVE;

            $columnsToUpdate[Entity::ACTIVATED_AT] = $now;

            $columnsToUpdate[Entity::UPDATED_AT] = $now;

            $this->repo->merchant_reward->update($queueMerchantReward, $columnsToUpdate);
        }
    }

    public function expireRewards()
    {
        $expiredMerchantRewards = $this->repo->merchant_reward->fetchExpiredMerchantReward();

        $columnsToUpdate = [];

        $columnsToUpdate[Entity::STATUS] = Entity::EXPIRED;

        $summary = [];

        $successCount = 0;
        $failedRewardIds = [];

        foreach ($expiredMerchantRewards as $expiredMerchantReward)
        {
            try
            {
                $this->trace->info(TraceCode::REWARD_TO_EXPIRE,
                    ['reward_id' => $expiredMerchantReward->getRewardId(),
                        'merchant_id' => $expiredMerchantReward->getMerchantId()]);

                $now = Carbon::now()->getTimestamp();

                $columnsToUpdate[Entity::UPDATED_AT] = $now;

                $this->repo->merchant_reward->update($expiredMerchantReward, $columnsToUpdate);

                $successCount += 1;
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e);

                $failedRewardIds[] = $expiredMerchantReward->getRewardId();
            }

        }

        $summary['success'] = $successCount;

        $summary['failures'] = $failedRewardIds;

        $this->moveQueuedRewardToLive($summary);

        return $summary;
    }

    /**
     * @param array $summary
     */
    public function moveQueuedRewardToLive(array & $summary)
    {
        $successQueueToLiveCountReward = 0;
        $failedQueueToLiveRewardIds = [];

        $queueMerchantRewards = $this->repo->merchant_reward->fetchQueueMerchantRewards();

        foreach ($queueMerchantRewards as $queueMerchantReward) {
            try {
                $this->trace->info(TraceCode::REWARD_QUEUE_TO_LIVE,
                    ['reward_id' => $queueMerchantReward->getRewardId(),
                        'merchant_id' => $queueMerchantReward->getMerchantId()]);


                $this->moveQueueRewardToLive($queueMerchantReward->getMerchantId());

                $successQueueToLiveCountReward += 1;

            } catch (\Exception $e) {
                $this->trace->traceException($e);

                $failedQueueToLiveRewardIds[] = $queueMerchantReward->getRewardId();
            }
        }

        $summary['success_queue_live'] = $successQueueToLiveCountReward;

        $summary['failures_queue_live'] = $failedQueueToLiveRewardIds;
    }
}
