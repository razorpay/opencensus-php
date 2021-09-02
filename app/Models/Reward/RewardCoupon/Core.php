<?php


namespace RZP\Models\Reward\RewardCoupon;

use Carbon\Carbon;
use Mail;
use RZP\Diag\EventCode;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Mail\Reward as RewardMail;

class Core extends Base\Core
{
    protected function getRedisKeyForCouponCount($rewardId)
    {
        return 'reward_'.$rewardId.'_unique_coupon_available_count';
    }

    protected function getUniqueCouponAvailableCountFromRedis($rewardId)
    {
        $redisKey = $this->getRedisKeyForCouponCount($rewardId);

        return $this->app['cache']->get($redisKey);
    }

    protected function setUniqueCouponAvailableCountInRedis($rewardId, $uniqueCouponAvailableCount)
    {
        $redisKey = $this->getRedisKeyForCouponCount($rewardId);

        $ttl = 365 * 24 * 60 * 60; // ttl of 1 year (in seconds)

        $this->app['cache']->put($redisKey, $uniqueCouponAvailableCount, $ttl);
    }

    protected function adjustUniqueCouponCountInRedis($rewardEntity, $rewardId)
    {
        $uniqueCouponAvailableCount = $this->getUniqueCouponAvailableCountFromRedis($rewardId);

        $uniqueCouponAvailableCount = $uniqueCouponAvailableCount - 1;

        $this->setUniqueCouponAvailableCountInRedis($rewardId, $uniqueCouponAvailableCount);

        $genericCoupon = $rewardEntity->getCouponCode();

        if(isset($genericCoupon) === true and $uniqueCouponAvailableCount <= 0)
        {
            //If generic coupon present as backup, can use up all the unique coupons
            $this->repo->reward->setUniqueCouponsExhaustedForReward($rewardId);
        }
        elseif(isset($genericCoupon) === false and $uniqueCouponAvailableCount <= 100)
        {
            //If no generic coupon, stop showing the reward when 100 coupons left
            //to avoid race condition where reward shown on checkout but all coupons are used
            $this->repo->reward->setUniqueCouponsExhaustedForReward($rewardId);
        }

        /*
         * Based on the count, send mails at threshold
         * Send analytics events
         */
        if(in_array($uniqueCouponAvailableCount, [100, 1000, 5000, 10000]))
        {
            $this->sendCouponCountThresholdMail($rewardEntity, $uniqueCouponAvailableCount);
        }
    }

    protected function fetchAndUpdateCouponCodeFromDB($rewardId)
    {
        return $this->repo->transaction(function () use ($rewardId)
            {
                $rewardCoupon = (new Repository)->fetchCouponByRewardIdWithLock($rewardId);

                if(isset($rewardCoupon))
                {
                    $this->repo->reward_coupon->updateCouponStatus($rewardCoupon);

                    return $rewardCoupon->getCouponCode();
                }

                return null;
            });
    }

    public function create($uniqueCouponCodes, $rewardId)
    {
        $this->trace->info(TraceCode::REWARD_UNIQUE_COUPON_CREATE_REQUEST);

        $now = Carbon::now()->getTimestamp();

        $offset = 0;
        $chunkSize = 10000;
        $failedCouponsCount = 0;
        $failedCoupons = [];
        $uniqueCouponsSlice = [];

        $uniqueCouponCodes = array_unique($uniqueCouponCodes);

        $totalRecordCount = sizeof($uniqueCouponCodes);

        while($offset < $totalRecordCount)
        {
            try
            {
                $uniqueCouponEntitiesToSave = [];

                $uniqueCouponsSlice = array_slice($uniqueCouponCodes, $offset, $chunkSize);

                foreach($uniqueCouponsSlice as $uniqueCouponCode)
                {
                    $uniqueCouponEntitiesToSave[] = [
                        Entity::REWARD_ID      => $rewardId,
                        Entity::COUPON_CODE    => $uniqueCouponCode,
                        Entity::STATUS         => Entity::AVAILABLE,
                        Entity::CREATED_AT     => $now,
                        Entity::UPDATED_AT     => $now
                    ];
                }

                Entity::insert($uniqueCouponEntitiesToSave);

                $this->trace->info(TraceCode::REWARD_COUPON_SUCCESSFUL_CHUNK, [
                    'reward_id' => $rewardId,
                    'offset'    => $offset,
                    'total'     => $totalRecordCount
                ]);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException($e, null, TraceCode::REWARD_UNIQUE_COUPON_INSERT_ERROR);

                $failedCouponsCount += sizeof($uniqueCouponsSlice);

                $failedCoupons = array_merge($failedCoupons, $uniqueCouponsSlice);

                $this->trace->info(TraceCode::REWARD_COUPON_FAILURE_CHUNK, [
                    'reward_id'            => $rewardId,
                    'offset'               => $offset,
                    'total'                => $totalRecordCount,
                    'failed_coupons_chunk' => $uniqueCouponsSlice
                ]);
            }
            $offset += $chunkSize;
        }

        $uniqueCouponAvailableCount = $this->repo->reward_coupon->getAvailableCouponCount($rewardId);

        $this->setUniqueCouponAvailableCountInRedis($rewardId, $uniqueCouponAvailableCount);

        $this->trace->info(TraceCode::REWARD_UNIQUE_COUPON_CREATE_RESULT, [
            'reward_id'               => $rewardId,
            'total'                   => $totalRecordCount,
            'failed_coupons_count'    => $failedCouponsCount,
            'available_coupons_count' => $uniqueCouponAvailableCount
        ]);

        return [
            'failed_coupons_count'          => $failedCouponsCount,
            'failed_coupons'                => $failedCoupons,
            'unique_coupon_available_count' => $uniqueCouponAvailableCount
        ];
    }

    public function getUniqueCouponCodeForReward($rewardEntity)
    {
        try
        {
            $rewardId = $rewardEntity->getId();

            $uniqueCouponAvailableCount = $this->getUniqueCouponAvailableCountFromRedis($rewardId) ?? 0;

            if($uniqueCouponAvailableCount <= 0)
            {
                $this->repo->reward->setUniqueCouponsExhaustedForReward($rewardId);

                return null;
            }

            $uniqueCouponCode = $this->fetchAndUpdateCouponCodeFromDB($rewardId);

            $this->adjustUniqueCouponCountInRedis($rewardEntity, $rewardId);

            return $uniqueCouponCode;
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::GET_UNIQUE_COUPON_CODE_ERROR);

            return null;
        }

    }

    public function sendCouponCountThresholdMail($rewardEntity, $uniqueCouponAvailableCount)
    {
        try {
            $subject = $uniqueCouponAvailableCount.' Unique Coupons Remaining for Reward Id - '.$rewardEntity->getId();

            if($uniqueCouponAvailableCount === 0)
            {
                $subject = 'Unique Coupons completely exhausted case. Please investigate.';
            }

            $data = [
                'subject'           => $subject,
                'brand_name'        => $rewardEntity->getBrandName(),
                'advertiser_id'     => $rewardEntity->getAdvertiserId(),
                'reward_id'         => $rewardEntity->getId(),
                'reward_name'       => $rewardEntity->getName(),
                'count'             => $uniqueCouponAvailableCount,
                'generic_present'   => $rewardEntity->getCouponCode() !== null ? 'Yes' : 'No'
            ];

            Mail::queue(new RewardMail\CouponCountThreshold($subject, $data));

            $properties = [
                'reward_id'         => $data['reward_id'],
                'count'             => $data['count'],
                'generic_present'   => $data['generic_present']
            ];

            $this->app['diag']->trackRewardEvent(EventCode::REWARD_COUPON_COUNT_THRESHOLD, null, null, $properties);

        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::COUPON_COUNT_THRESHOLD_MAIL_ERROR);
        }
    }

    public function triggerRewardCouponDistributedEvent($properties)
    {
        try
        {
            $this->trace->info(TraceCode::REWARD_COUPON_DISTRIBUTED_EVENT, $properties);

            $this->app['diag']->trackRewardEvent(EventCode::REWARD_COUPON_DISTRIBUTED, null, null, $properties);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, null, TraceCode::REWARD_COUPON_DISTRIBUTED_EVENT_ERROR);
        }
    }
}
