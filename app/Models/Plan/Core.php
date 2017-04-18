<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Schedule;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant)
    {
        $plan = (new Entity)->build($input);

        //
        // Transaction on live and test is required because
        // schedule is created in both live and test.
        //
        $this->repo->transactionOnLiveAndTest(
            function()
            use ($plan, $merchant, $input)
            {
                $plan->merchant()->associate($merchant);

                $this->createSchedule($plan, $input);

                // We need to do this because `createSchedule` will change the
                // connection to live.
                $plan->setConnection($this->mode);

                $this->repo->saveOrFail($plan);
            });

        return $plan;
    }

    protected function createSchedule(Entity $plan, array $input)
    {
        // TODO: Decide on the name for the schedule.

        $extraInput = [
            Schedule\Entity::NAME => $input[Entity::NAME],
        ];

        $scheduleInput = array_merge($input[Entity::SCHEDULE], $extraInput);

        $schedule = (new Schedule\Core)->createSchedule($scheduleInput, $plan->merchant);

        $plan->schedule()->associate($schedule);
    }
}
