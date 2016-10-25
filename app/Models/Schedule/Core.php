<?php

namespace RZP\Models\Schedule;

use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createSchedule($input)
    {
        $schedule = (new Entity)->build($input);

        $schedule->generateId();

        $schedule->setMerchantId(Account::SHARED_ACCOUNT);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }

    public function editSchedule($schedule, $input)
    {
        $schedule->edit($input);

        $this->repo->saveOrFail($schedule);

        return $schedule;
    }
}
