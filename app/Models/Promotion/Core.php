<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Models\Schedule\Anchor;
use RZP\Models\{Base, Schedule, Merchant};
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    public function create(array $input): Entity
    {
        $partner = null;

        if (empty($input[Entity::PARTNER_ID]) === false)
        {
            /** @var Merchant\Entity $merchant */
            $partner = $this->repo->merchant->findOrFailPublic($input[Entity::PARTNER_ID]);

            (new Merchant\Validator)->validateIsNonPurePlatformPartner($partner);

            unset($input[Entity::PARTNER_ID]);
        }

        return $this->repo->transaction(function() use ($input, $partner)
        {
            $promotion = (new Entity)->build($input);

            if ($promotion->doCreditsExpire() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            if (empty($partner) === false)
            {
                $promotion->partner()->associate($partner);
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

            if ($promotion->doCreditsExpire() === true)
            {
                $schedule = $this->createSchedule($input);

                $promotion->schedule()->associate($schedule);
            }

            $this->repo->saveOrFail($promotion);

            return $promotion;
        });
    }

    public function processTasks(Base\PublicCollection $tasks, int $timestamp): array
    {
        return (new MerchantPromotion\Core)->processTasks($tasks, $timestamp);
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

    protected function createSchedule(array $input): Schedule\Entity
    {
        $period = $input[Entity::CREDITS_EXPIRY_PERIOD];

        $interval = $input[Entity::CREDITS_EXPIRY_INTERVAL];

        $anchor = $this->getAnchorForPromotion($period);

        $schedule = $this->repo->schedule->getScheduleByPeriodIntervalAnchorAndType(
            $period, $interval, $anchor, Schedule\Type::PROMOTION);

        if ($schedule === null)
        {
            $scheduleName =  $interval . '/' . $period;

            $scheduleInput = [
                Schedule\Entity::NAME     => $scheduleName,
                Schedule\Entity::INTERVAL => $interval,
                Schedule\Entity::PERIOD   => $period,
                Schedule\Entity::ANCHOR   => $anchor,
                Schedule\Entity::TYPE     => Schedule\Type::PROMOTION
            ];

            $schedule = (new Schedule\Core)->createSchedule($scheduleInput);
        }

        return $schedule;
    }

    protected function getAnchorForPromotion(string $period)
    {
       $anchor = null;

       //For hourly AND daily, anchor is not significant

        $unAnchoredPeriods = [
            Schedule\Period::HOURLY,
            Schedule\Period::DAILY
        ];

        if (in_array($period, $unAnchoredPeriods, true) === true)
        {
            return null;
        }

       $currentTime = Carbon::now(Timezone::IST);

       $anchor = Anchor::getAnchor($period, $currentTime);

       return $anchor;
    }
}
