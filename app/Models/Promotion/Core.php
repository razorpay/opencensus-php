<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Models\Schedule;

class Core extends Base\Core
{
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
            Schedule\Entity::NAME       => $input['credits_expiry_interval'] . '/' . $input['credits_expiry_period'],
            Schedule\Entity::INTERVAL   => $input['credits_expiry_interval'],
            Schedule\Entity::PERIOD     => $input['credits_expiry_period'],
        ];

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput);

        return $schedule;
    }

    protected function editSchedule($schedule, array $input)
    {
        $scheduleInput = [
            Schedule\Entity::INTERVAL   => $input['credits_expiry_interval'],
        ];

        $schedule = (new Schedule\Core)->editSchedule($schedule, $scheduleInput);

        return $schedule;
    }
}
