<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';
    const CREDITS_EXPIRY_PERIOD = 'credits_expiry_period';

    public function create(array $input)
    {
        $promotion = (new Entity)->build($input);

        if ($promotion->doCreditsExpire() === true)
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

        if ($promotion->doCreditsExpire() === true)
        {
            $schedule = $promotion->schedule;

            if (empty($schedule) === true)
            {
                $schedule = $this->createSchedule($input);
            }
            else
            {
                $schedule = $this->editSchedule($schedule, $input);
            }

            $promotion->schedule()->associate($schedule);
        }

        $this->repo->saveOrFail($promotion);

        return $promotion;
    }

    protected function createSchedule(array $input)
    {
        $scheduleInput = [
            Schedule\Entity::NAME       => $input[self::CREDITS_EXPIRY_INTERVAL] . '/' . $input[self::CREDITS_EXPIRY_PERIOD],
            Schedule\Entity::INTERVAL   => $input[self::CREDITS_EXPIRY_INTERVAL],
            Schedule\Entity::PERIOD     => $input[self::CREDITS_EXPIRY_PERIOD],
        ];

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function editSchedule($schedule, array $input)
    {
        $scheduleInput = [
            Schedule\Entity::INTERVAL   => $input[self::CREDITS_EXPIRY_INTERVAL],
        ];

        $schedule = (new Schedule\Core)->editSchedule($schedule, $scheduleInput);

        return $schedule;
    }

    protected function isUsed()
    {
        //check entry in merchant promotions
    }
}
