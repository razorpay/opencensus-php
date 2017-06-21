<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Schedule;
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    public function create(array $input)
    {
        $promotion = (new Entity)->build($input);

        if ($promotion->areCreditsExpirable() === true)
        {
            $schedule = $this->createSchedule($input);

            $promotion->schedule()->associate($schedule);
        }

        $this->repo->saveOrFail($promotion);

        return $promotion;
    }

    public function update(Entity $promotion, array $input)
    {
        if ($this->isUsed($promotion) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Editing a used promotion is not allowed');
        }

        $promotion->edit($input);

        if ($promotion->areCreditsExpirable() === true)
        {
            $schedule = $this->createSchedule($input);

            $promotion->schedule()->associate($schedule);
        }

        $this->repo->saveOrFail($promotion);

        return $promotion;
    }

    //TODO
    public function processTasks($tasks)
    {
        return (new MerchantPromotion\Core)->processTasks($tasks);
    }

    public function isUsed(Entity $promotion): bool
    {
        $usedCount = $this->repo->merchant_promotion
                                        ->findUsedCountByPromotionId($promotion->getId());

        if ($usedCount === 0)
        {
            return false;
        }

        return true;
    }

    protected function createSchedule(array $input)
    {
        $schedule = $this->repo->schedule->getScheduleByPeriodAndInterval(
            $input[Entity::CREDITS_EXPIRY_PERIOD], $input[Entity::CREDITS_EXPIRY_INTERVAL]);

        if ($schedule === null)
        {
            $scheduleName =  $input[Entity::CREDITS_EXPIRY_INTERVAL] . '/' .
                            $input[Entity::CREDITS_EXPIRY_PERIOD];

            $scheduleInput = [
                Schedule\Entity::NAME       => $scheduleName,
                Schedule\Entity::INTERVAL   => $input[Entity::CREDITS_EXPIRY_INTERVAL],
                Schedule\Entity::PERIOD     => $input[Entity::CREDITS_EXPIRY_PERIOD],
            ];

            $schedule = (new Schedule\Core)->createSchedule($scheduleInput);
        }

        return $schedule;
    }
}
