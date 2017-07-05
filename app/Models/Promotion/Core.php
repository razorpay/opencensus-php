<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Schedule;
use RZP\Models\Schedule\Anchor;
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        return $this->repo->transaction(function() use ($input)
        {
            $promotion = (new Entity)->build($input);

            if ($promotion->areCreditsExpirable() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            $this->repo->saveOrFail($promotion);

            return $promotion;
        });
    }

    public function update(Entity $promotion, array $input): Entity
    {
        if ($this->isUsed($promotion) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing a used promotion is not allowed');
        }

        return $this->repo->transaction(
            function() use ($promotion, $input)
        {
            $promotion->edit($input);

            if ($promotion->areCreditsExpirable() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            $this->repo->saveOrFail($promotion);

            return $promotion;
        });
    }

    public function processTasks($tasks)
    {
        return (new MerchantPromotion\Core)->processTasks($tasks);
    }

    public function isUsed(Entity $promotion): bool
    {
        $usedCount = $this->repo
                          ->merchant_promotion
                          ->getCountByPromotionId($promotion->getId());

        if ($usedCount === 0)
        {
            return false;
        }

        return true;
    }

    protected function createSchedule(array $input)
    {
        $period = $input[Entity::CREDITS_EXPIRY_PERIOD];

        $interval = $input[Entity::CREDITS_EXPIRY_INTERVAL];

        $anchor = $this->getAnchorForPromotion($period);

        $schedule = $this->repo->schedule->getScheduleByPeriodIntervalAndAnchor(
            $period, $interval, $anchor);

        if ($schedule === null)
        {
            $scheduleName =  $interval . '/' . $period;

            $scheduleInput = [
                Schedule\Entity::NAME     => $scheduleName,
                Schedule\Entity::INTERVAL => $interval,
                Schedule\Entity::PERIOD   => $period,
                Schedule\Entity::ANCHOR   => $anchor,
            ];

            $schedule = (new Schedule\Core)->createSchedule($scheduleInput);
        }

        return $schedule;
    }

    protected function getAnchorForPromotion($period)
    {
       $anchor = null;

       // Tomorrow because we want to expire the credits 12
       // of next day not the same day as promotion was applied
       $day = Carbon::tomorrow('Asia/Kolkata');

       $anchor = $day->{Anchor::CHECKS[$period]};

       return $anchor;
    }
}
