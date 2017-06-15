<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Schedule;
use RZP\Models\Merchant\Promotion as MerchantPromotion;

class Core extends Base\Core
{
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';
    const CREDITS_EXPIRY_PERIOD = 'credits_expiry_period';

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
            $schedule = $promotion->schedule;

            $schedule = $this->addOrUpdateSchedule($schedule, $input);

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
        $merchantPromotion = $this->repo->merchant_promotion
                                        ->findByPromotionId($promotion->getId());

        if ($merchantPromotion === null)
        {
            return false;
        }

        return true;
    }

    protected function createSchedule(array $input)
    {
        $scheduleName =  $input[self::CREDITS_EXPIRY_INTERVAL] . '/' .
                            $input[self::CREDITS_EXPIRY_PERIOD];

        $scheduleInput = [
            Schedule\Entity::NAME       => $scheduleName,
            Schedule\Entity::INTERVAL   => $input[self::CREDITS_EXPIRY_INTERVAL],
            Schedule\Entity::PERIOD     => $input[self::CREDITS_EXPIRY_PERIOD],
        ];

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function editSchedule(Schedule\Entity $schedule, array $input)
    {
        $scheduleInput = [
            Schedule\Entity::INTERVAL   => $input[self::CREDITS_EXPIRY_INTERVAL],
        ];

        $schedule = (new Schedule\Core)->editSchedule($schedule, $scheduleInput);

        return $schedule;
    }

    protected function addOrUpdateSchedule(Schedule\Entity $schedule, array $input)
    {
        if ($schedule === null)
        {
            return $this->createSchedule($input);
        }
        else
        {
            return $this->editSchedule($schedule, $input);
        }
    }
}
